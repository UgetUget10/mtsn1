<?php

namespace App\Filament\Resources\Achievements\Actions;

use App\Filament\Support\SimpleCsvImporter;
use App\Models\Achievement;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;

/**
 * Import massal Prestasi dari CSV/XLSX — berguna untuk mencatat banyak
 * prestasi lomba sekaligus (mis. rekap akhir tahun ajaran). Kolom: title*,
 * student_name, level (kecamatan/kota/provinsi/nasional/internasional),
 * year, description. Gambar tidak diimport — diunggah manual per prestasi.
 */
class ImportAchievementsAction
{
    private const VALID_LEVELS = ['kecamatan', 'kota', 'provinsi', 'nasional', 'internasional'];

    public static function make(): Action
    {
        return Action::make('importAchievements')
            ->label('Import')
            ->icon(Heroicon::OutlinedArrowUpTray)
            ->color('gray')
            ->modalHeading('Import Prestasi')
            ->modalDescription('Unggah CSV/XLSX berkolom: title (wajib), student_name, level (kecamatan/kota/provinsi/nasional/internasional), year, description. Baris pertama = header. Setiap baris selalu ditambahkan sebagai prestasi baru (tidak ada pencocokan data lama).')
            ->modalSubmitActionLabel('Jalankan Import')
            ->schema([
                FileUpload::make('file')
                    ->label('Berkas')
                    ->required()
                    ->acceptedFileTypes([
                        'text/csv',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        '.csv', '.xlsx',
                    ])
                    ->disk('local')
                    ->directory('imports')
                    ->visibility('private'),
            ])
            ->action(function (array $data) {
                $relativePath = is_array($data['file']) ? ($data['file'][0] ?? null) : $data['file'];
                if (! $relativePath) {
                    Notification::make()->title('Berkas tidak ditemukan.')->danger()->send();

                    return;
                }

                $absolutePath = Storage::disk('local')->path($relativePath);
                $format = str_ends_with(strtolower($relativePath), '.xlsx') ? 'xlsx' : 'csv';

                try {
                    $rows = SimpleCsvImporter::read($absolutePath, $format);
                    $result = self::import($rows);
                } catch (\Throwable $e) {
                    report($e);
                    Notification::make()
                        ->title('Import gagal diproses.')
                        ->body($e->getMessage())
                        ->danger()
                        ->persistent()
                        ->send();

                    return;
                } finally {
                    Storage::disk('local')->delete($relativePath);
                }

                Notification::make()
                    ->title('Import selesai')
                    ->body("Dibuat: {$result['created']} · Dilewati: {$result['skipped']}")
                    ->success()
                    ->send();
            });
    }

    /**
     * @param  array<int, array<string, string>>  $rows
     * @return array{created: int, skipped: int}
     */
    private static function import(array $rows): array
    {
        $result = ['created' => 0, 'skipped' => 0];

        foreach ($rows as $row) {
            $title = $row['title'] ?? $row['prestasi'] ?? null;
            if (! $title) {
                $result['skipped']++;

                continue;
            }

            $level = strtolower($row['level'] ?? $row['tingkat'] ?? '');

            Achievement::create([
                'title' => $title,
                'student_name' => $row['student_name'] ?? $row['nama_siswa'] ?? null ?: null,
                'level' => in_array($level, self::VALID_LEVELS, true) ? $level : null,
                'year' => is_numeric($row['year'] ?? $row['tahun'] ?? null) ? (int) ($row['year'] ?? $row['tahun']) : null,
                'description' => $row['description'] ?? $row['deskripsi'] ?? null ?: null,
            ]);

            $result['created']++;
        }

        return $result;
    }
}
