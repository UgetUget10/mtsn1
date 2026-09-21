<?php

namespace App\Models\Concerns;

use App\Models\Revision;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Auth;

/**
 * Menyimpan snapshot atribut model SEBELUM diubah — meniru wp_post_revisions.
 *
 * - Snapshot diambil pada event `updating` (nilai lama, "original").
 * - Hanya menyimpan bila salah satu kolom di $revisionable ikut berubah.
 * - Menyimpan maksimal `revisionsToKeep()` revisi terakhir (WP: WP_POST_REVISIONS).
 *
 * Model yang memakai trait ini WAJIB mendefinisikan properti:
 *   protected array $revisionable = ['title', 'body', ...];
 */
trait HasRevisions
{
    public static function bootHasRevisions(): void
    {
        static::updating(function ($model): void {
            $watched = $model->revisionable ?? [];

            if (empty($watched)) {
                return;
            }

            $dirty = array_keys($model->getDirty());
            if (empty(array_intersect($watched, $dirty))) {
                return;
            }

            $snapshot = [];
            foreach ($watched as $key) {
                // getRawOriginal agar nilai translatable tersimpan sebagai JSON apa adanya.
                $snapshot[$key] = $model->getRawOriginal($key);
            }

            $model->revisions()->create([
                'user_id' => Auth::id(),
                'data' => $snapshot,
                'reason' => $model->revisionReason ?? null,
            ]);

            $model->pruneRevisions();
        });

        static::deleted(function ($model): void {
            // Hard delete (force) — buang revisi yatim. Soft delete membiarkannya.
            if (method_exists($model, 'isForceDeleting') && ! $model->isForceDeleting()) {
                return;
            }
            $model->revisions()->delete();
        });
    }

    public function revisions(): MorphMany
    {
        return $this->morphMany(Revision::class, 'revisionable')->latest();
    }

    public function revisionsToKeep(): int
    {
        return (int) config('editorial.revisions_to_keep', 20);
    }

    public function pruneRevisions(): void
    {
        $keep = $this->revisionsToKeep();
        if ($keep <= 0) {
            return;
        }

        $ids = $this->revisions()->skip($keep)->take(PHP_INT_MAX)->pluck('id');
        if ($ids->isNotEmpty()) {
            Revision::whereIn('id', $ids)->delete();
        }
    }

    /**
     * Kembalikan model ke isi sebuah revisi. Tidak menyentuh slug/status —
     * hanya kolom yang memang disnapshot — persis "Restore this revision" WP.
     */
    public function restoreRevision(Revision $revision): void
    {
        $this->revisionReason = 'restore';
        $this->forceFill($revision->data);
        $this->save();
    }
}
