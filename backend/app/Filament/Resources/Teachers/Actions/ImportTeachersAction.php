<?php

namespace App\Filament\Resources\Teachers\Actions;

use App\Filament\Support\SimpleCsvImporter;
use App\Models\Teacher;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;

/**
 * Import massal Guru & Tendik dari CSV/XLSX — berguna saat awal tahun ajaran
 * saat daftar guru/tendik baru diinput sekaligus. Kolom: name*, nip,
 * position, subject, email, group (pimpinan/guru/tendik), is_active.
 * Foto tidak diimport (perlu diunggah manual per guru) — spreadsheet tidak
 * cocok membawa berkas gambar.
 */
class ImportTeachersAction
{
    public static function make(): Action
    {
        return Action::make('importTeachers')
            ->label('Import')
            ->icon(Heroicon::OutlinedArrowUpTray)
            ->color('gray')
            ->modalHeading('Import Guru & Tendik')
            ->modalDescription('Unggah CSV/XLSX berkolom: name (wajib), nip, position, subject, email, group (pimpinan/guru/tendik), is_active (1/0). Baris pertama = header. Data yang sudah ada (dicocokkan lewat NIP) diperbarui; sisanya ditambahkan baru.')
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
                    ->body("Dibuat: {$result['created']} · Diperbarui: {$result['updated']} · Dilewati: {$result['skipped']}")
                    ->success()
                    ->send();
            });
    }

    /**
     * @param  array<int, array<string, string>>  $rows
     * @return array{created: int, updated: int, skipped: int}
     */
    private static function import(array $rows): array
    {
        $result = ['created' => 0, 'updated' => 0, 'skipped' => 0];
        $validGroups = ['pimpinan', 'guru', 'tendik'];
        $nextOrder = (int) (Teacher::max('order') ?? 0);

        foreach ($rows as $row) {
            $name = $row['name'] ?? $row['nama'] ?? null;
            if (! $name) {
                $result['skipped']++;

                continue;
            }

            $nip = $row['nip'] ?: null;
            $existing = $nip ? Teacher::where('nip', $nip)->first() : null;
            $teacher = $existing ?: new Teacher();

            $teacher->name = $name;
            $teacher->nip = $nip;
            $teacher->position = $row['position'] ?? $row['jabatan'] ?? null ?: null;
            $teacher->subject = $row['subject'] ?? $row['mapel'] ?? null ?: null;
            $teacher->email = $row['email'] ?? null ?: null;
            $group = strtolower($row['group'] ?? $row['kelompok'] ?? '');
            $teacher->group = in_array($group, $validGroups, true) ? $group : 'guru';
            $teacher->is_active = in_array(strtolower($row['is_active'] ?? '1'), ['1', 'true', 'yes', 'ya'], true);

            if (! $existing) {
                $teacher->order = ++$nextOrder;
            }

            $teacher->save();

            $existing ? $result['updated']++ : $result['created']++;
        }

        return $result;
    }
}
