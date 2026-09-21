<?php

namespace App\Filament\Pages;

use App\Console\Commands\PublishScheduledPosts;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * "Site Health" ala Tools → Site Health WordPress — ringkasan status sistem
 * untuk diagnosa cepat tanpa perlu akses server (PHP version, storage,
 * koneksi database, status scheduler). HANYA super_admin: ini info teknis
 * server, bukan konten yang perlu dilihat editor/kontributor.
 */
class SiteHealth extends Page
{
    protected string $view = 'filament.pages.site-health';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHeart;

    protected static \UnitEnum|string|null $navigationGroup = 'Pengaturan';

    protected static ?string $navigationLabel = 'Site Health';

    protected static ?string $title = 'Site Health';

    protected static ?int $navigationSort = 98;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    /** @return array<int, array{label: string, value: string, status: 'good'|'warning'|'critical'}> */
    public function getChecks(): array
    {
        return [
            ...$this->environmentChecks(),
            ...$this->storageChecks(),
            ...$this->schedulerChecks(),
            ...$this->databaseChecks(),
        ];
    }

    /** @return array<int, array{label: string, value: string, status: 'good'|'warning'|'critical'}> */
    private function environmentChecks(): array
    {
        $phpVersion = PHP_VERSION;
        $minPhp = '8.2.0';

        return [
            [
                'label' => 'Versi PHP',
                'value' => $phpVersion,
                'status' => version_compare($phpVersion, $minPhp, '>=') ? 'good' : 'critical',
            ],
            [
                'label' => 'Versi Laravel',
                'value' => app()->version(),
                'status' => 'good',
            ],
            [
                'label' => 'Mode Debug',
                'value' => config('app.debug') ? 'Aktif' : 'Nonaktif',
                // Debug aktif di production membocorkan detail error ke pengunjung — kritis.
                'status' => config('app.debug') && config('app.env') === 'production' ? 'critical' : 'good',
            ],
            [
                'label' => 'Lingkungan (APP_ENV)',
                'value' => config('app.env'),
                'status' => 'good',
            ],
        ];
    }

    /** @return array<int, array{label: string, value: string, status: 'good'|'warning'|'critical'}> */
    private function storageChecks(): array
    {
        $checks = [];

        try {
            $testFile = 'health-check-'.time().'.txt';
            Storage::disk('public')->put($testFile, 'ok');
            $writable = Storage::disk('public')->exists($testFile);
            Storage::disk('public')->delete($testFile);
        } catch (\Throwable) {
            $writable = false;
        }

        $checks[] = [
            'label' => 'Disk penyimpanan publik dapat ditulis',
            'value' => $writable ? 'Ya' : 'TIDAK — unggah berkas akan gagal',
            'status' => $writable ? 'good' : 'critical',
        ];

        $freeBytes = @disk_free_space(storage_path());
        if ($freeBytes !== false) {
            $freeGb = round($freeBytes / 1024 / 1024 / 1024, 1);
            $checks[] = [
                'label' => 'Ruang disk tersisa',
                'value' => "{$freeGb} GB",
                'status' => $freeGb < 1 ? 'critical' : ($freeGb < 5 ? 'warning' : 'good'),
            ];
        }

        return $checks;
    }

    /** @return array<int, array{label: string, value: string, status: 'good'|'warning'|'critical'}> */
    private function schedulerChecks(): array
    {
        $lastRun = Cache::get(PublishScheduledPosts::HEARTBEAT_KEY);

        if (! $lastRun) {
            return [[
                'label' => 'Penjadwal (cron / Task Scheduler)',
                'value' => 'Belum pernah terekam jalan',
                'status' => 'warning',
            ]];
        }

        $diff = Carbon::parse($lastRun);
        $stale = $diff->lt(now()->subMinutes(10));

        return [[
            'label' => 'Penjadwal (cron / Task Scheduler)',
            'value' => 'Terakhir jalan '.$diff->diffForHumans(),
            'status' => $stale ? 'critical' : 'good',
        ]];
    }

    /** @return array<int, array{label: string, value: string, status: 'good'|'warning'|'critical'}> */
    private function databaseChecks(): array
    {
        try {
            DB::connection()->getPdo();
            $connected = true;
        } catch (\Throwable) {
            $connected = false;
        }

        return [[
            'label' => 'Koneksi basis data',
            'value' => $connected ? 'Tersambung ('.config('database.default').')' : 'GAGAL TERSAMBUNG',
            'status' => $connected ? 'good' : 'critical',
        ]];
    }
}
