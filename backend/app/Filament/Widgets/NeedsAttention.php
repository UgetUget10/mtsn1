<?php

namespace App\Filament\Widgets;

use App\Console\Commands\PublishScheduledPosts;
use App\Models\NotFoundLog;
use App\Models\Post;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * "Butuh perhatian" — hal-hal yang mudah terlewat karena tidak masuk hitungan
 * status biasa: 404 baru yang belum ditangani, dan berita terjadwal yang
 * publikasinya sudah dekat. Kartu disembunyikan (nilai 0) kalau tidak relevan.
 */
class NeedsAttention extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $outstanding404 = NotFoundLog::outstanding()->count();
        $recent404 = NotFoundLog::outstanding()->where('last_seen_at', '>=', now()->subDays(7))->count();

        $dueSoon = Post::where('status', Post::STATUS_SCHEDULED)
            ->whereBetween('published_at', [now(), now()->addDay()])
            ->count();

        $scheduledTotal = Post::where('status', Post::STATUS_SCHEDULED)->count();

        // Scheduler mati (Task Scheduler/cron berhenti memanggil
        // `schedule:run`) berarti post terjadwal TIDAK PERNAH otomatis
        // terbit — tapi tidak ada error yang terlihat di mana pun. Kartu ini
        // hanya tampil kalau ada post terjadwal DAN detak jantung basi/hilang,
        // supaya tidak mengganggu saat memang tidak ada yang dijadwalkan.
        $lastRun = Cache::get(PublishScheduledPosts::HEARTBEAT_KEY);
        $schedulerStale = $scheduledTotal > 0
            && (! $lastRun || Carbon::parse($lastRun)->lt(now()->subMinutes(10)));

        return [
            ...($schedulerStale ? [
                Stat::make('Penjadwal berita tidak jalan', $lastRun ? 'Terakhir '.Carbon::parse($lastRun)->diffForHumans() : 'Belum pernah jalan')
                    ->description('Post terjadwal tidak akan otomatis terbit. Periksa cron/Task Scheduler server (php artisan schedule:run).')
                    ->descriptionIcon('heroicon-m-exclamation-triangle')
                    ->icon('heroicon-o-clock')
                    ->color('danger'),
            ] : []),
            Stat::make('404 belum ditangani', $outstanding404)
                ->description($recent404 > 0
                    ? "{$recent404} baru dalam 7 hari terakhir"
                    : 'tidak ada kunjungan baru minggu ini')
                ->descriptionIcon($outstanding404 ? 'heroicon-m-exclamation-triangle' : null)
                ->icon('heroicon-o-link-slash')
                ->color($outstanding404 ? 'danger' : 'gray')
                ->url(route('filament.admin.resources.not-found-logs.index')),

            Stat::make('Terjadwal terbit < 24 jam', $dueSoon)
                ->description($scheduledTotal > $dueSoon
                    ? ($scheduledTotal - $dueSoon).' lainnya terjadwal setelah itu'
                    : 'tidak ada jadwal lain menunggu')
                ->descriptionIcon($dueSoon ? 'heroicon-m-clock' : null)
                ->icon('heroicon-o-calendar')
                ->color($dueSoon ? 'warning' : 'gray')
                ->url(route('filament.admin.resources.posts.index', ['tableFilters[status][value]' => Post::STATUS_SCHEDULED])),
        ];
    }
}
