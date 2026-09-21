<?php

namespace App\Console\Commands;

use App\Models\Achievement;
use App\Models\Document;
use App\Models\Extracurricular;
use App\Models\Gallery;
use App\Models\GalleryItem;
use App\Models\Post;
use App\Models\Setting;
use App\Models\Slider;
use App\Models\Teacher;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Migrasi kolom path string lama (cover, photo, image, logo, dst.) yang
 * tersimpan di disk `public` menjadi entri spatie/laravel-medialibrary
 * pada koleksi yang sudah didaftarkan di masing-masing model.
 *
 * Kolom string lama TIDAK dihapus oleh command ini — tetap dipertahankan
 * sebagai jalur rollback sampai frontend & data terverifikasi stabil.
 */
class MigrateLegacyMedia extends Command
{
    protected $signature = 'media:migrate-legacy {--dry-run : Tampilkan apa yang akan dimigrasi tanpa menulis apa pun}';

    protected $description = 'Migrasikan path media lama (string) ke spatie/laravel-medialibrary';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $migrated = 0;
        $skippedMissing = 0;
        $skippedAlready = 0;
        $failed = 0;

        $jobs = [
            [Post::class, 'cover', 'cover'],
            [Slider::class, 'image', 'image'],
            [Teacher::class, 'photo', 'photo'],
            [Achievement::class, 'image', 'image'],
            [Extracurricular::class, 'image', 'image'],
            [Gallery::class, 'cover', 'cover'],
            [GalleryItem::class, 'path', 'image'],
            [Document::class, 'file', 'file'],
        ];

        foreach ($jobs as [$modelClass, $column, $collection]) {
            /** @var Model $modelClass */
            $modelClass::query()->whereNotNull($column)->where($column, '!=', '')->each(
                function (Model $model) use ($column, $collection, $dryRun, &$migrated, &$skippedMissing, &$skippedAlready, &$failed) {
                    $path = $model->getAttribute($column);

                    if (! is_string($path) || $path === '') {
                        return;
                    }

                    $label = $model::class."#{$model->getKey()}";

                    if (! Storage::disk('public')->exists($path)) {
                        $skippedMissing++;
                        $this->line("  <fg=yellow>hilang</> {$label} — {$path}");

                        return;
                    }

                    $existing = $model->getFirstMedia($collection);
                    if ($existing && $existing->getCustomProperty('source_path') === $path) {
                        $skippedAlready++;

                        return;
                    }

                    if ($dryRun) {
                        $migrated++;
                        $this->line("  <fg=green>migrasi</> {$label} — {$path} → koleksi '{$collection}'");

                        return;
                    }

                    try {
                        $model->addMediaFromDisk($path, 'public')
                            ->withCustomProperties(['source_path' => $path])
                            ->usingFileName(Str::random(8).'-'.basename($path))
                            ->toMediaCollection($collection);
                        $migrated++;
                        $this->line("  <fg=green>migrasi</> {$label} — {$path} → koleksi '{$collection}'");
                    } catch (\Throwable $e) {
                        // Konversi gambar (mis. thumbnail) bisa gagal karena berkas rusak/tidak
                        // standar (mis. profil ICC PNG yang salah) — entri media asli tetap
                        // tersimpan oleh medialibrary sebelum konversi dijalankan, jadi cukup
                        // catat kegagalan konversi dan lanjut ke item berikutnya.
                        $failed++;
                        $this->line("  <fg=red>gagal</> {$label} — {$path}: ".$e->getMessage());
                    }
                }
            );
        }

        // Setting: 4 baris key/value bertipe file, masing-masing punya koleksi 'file' sendiri.
        foreach (['logo', 'favicon', 'og_image', 'principal_photo'] as $key) {
            $setting = Setting::where('key', $key)->first();
            $path = $setting?->value;

            if (! $setting || ! is_string($path) || $path === '') {
                continue;
            }

            if (! Storage::disk('public')->exists($path)) {
                $skippedMissing++;
                $this->line("  <fg=yellow>hilang</> Setting[{$key}] — {$path}");

                continue;
            }

            $existing = $setting->getFirstMedia('file');
            if ($existing && $existing->getCustomProperty('source_path') === $path) {
                $skippedAlready++;

                continue;
            }

            if ($dryRun) {
                $migrated++;
                $this->line("  <fg=green>migrasi</> Setting[{$key}] — {$path} → koleksi 'file'");

                continue;
            }

            try {
                $setting->addMediaFromDisk($path, 'public')
                    ->withCustomProperties(['source_path' => $path])
                    ->usingFileName(Str::random(8).'-'.basename($path))
                    ->toMediaCollection('file');
                $migrated++;
                $this->line("  <fg=green>migrasi</> Setting[{$key}] — {$path} → koleksi 'file'");
            } catch (\Throwable $e) {
                $failed++;
                $this->line("  <fg=red>gagal</> Setting[{$key}] — {$path}: ".$e->getMessage());
            }
        }

        $this->newLine();
        $this->info(($dryRun ? '[DRY RUN] ' : '')."Selesai. Dimigrasi: {$migrated}, dilewati (sudah ada): {$skippedAlready}, dilewati (berkas hilang): {$skippedMissing}, gagal: {$failed}.");

        return self::SUCCESS;
    }
}
