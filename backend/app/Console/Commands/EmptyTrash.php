<?php

namespace App\Console\Commands;

use App\Models\Achievement;
use App\Models\Agenda;
use App\Models\Document;
use App\Models\Extracurricular;
use App\Models\Gallery;
use App\Models\Page;
use App\Models\Post;
use Illuminate\Console\Command;

/**
 * Hapus permanen item yang sudah di Trash lebih lama dari editorial.empty_trash_days
 * — setara cron `wp_scheduled_delete` / konstanta EMPTY_TRASH_DAYS di WordPress.
 */
class EmptyTrash extends Command
{
    protected $signature = 'content:empty-trash {--days= : Override umur hari; default dari config editorial}';

    protected $description = 'Hapus permanen konten di Trash yang melewati batas umur (wp: EMPTY_TRASH_DAYS)';

    /** @var array<class-string> */
    private array $models = [
        Post::class, Page::class, Gallery::class,
        Document::class, Agenda::class, Achievement::class, Extracurricular::class,
    ];

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?? config('editorial.empty_trash_days', 30));

        if ($days <= 0) {
            $this->warn('empty_trash_days <= 0 — pembersihan dinonaktifkan.');

            return self::SUCCESS;
        }

        $cutoff = now()->subDays($days);
        $total = 0;

        foreach ($this->models as $model) {
            $stale = $model::onlyTrashed()->where('deleted_at', '<=', $cutoff)->get();

            foreach ($stale as $record) {
                $record->forceDelete();
                $total++;
            }

            if ($stale->isNotEmpty()) {
                $this->line(class_basename($model).": {$stale->count()} dihapus permanen.");
            }
        }

        $this->info("Selesai. {$total} item dibuang dari Trash (> {$days} hari).");

        return self::SUCCESS;
    }
}
