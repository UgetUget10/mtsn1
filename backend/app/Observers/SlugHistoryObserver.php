<?php

namespace App\Observers;

use App\Models\Category;
use App\Models\Page;
use App\Models\Post;
use App\Models\Redirect;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Model;

/**
 * Saat slug sebuah konten berubah, catat redirect 301 dari path lama ke baru
 * supaya tautan/bookmark/hasil Google lama tidak mati — persis `_wp_old_slug`
 * di WordPress. Diikat ke Post, Page, Category di AppServiceProvider.
 */
class SlugHistoryObserver
{
    public function updated(Model $model): void
    {
        if (! $model->wasChanged('slug')) {
            return;
        }

        $old = $model->getOriginal('slug');
        $new = $model->slug;

        if (! $old || $old === $new) {
            return;
        }

        $from = $this->pathFor($model, $old);
        $to = $this->pathFor($model, $new);

        if (! $from || ! $to || $from === $to) {
            return;
        }

        Redirect::updateOrCreate(
            ['from_path' => $from],
            ['to_path' => $to, 'status' => 301, 'source' => 'slug-change'],
        );

        // Jika slug baru kebetulan sama dengan sebuah from_path lama, buang
        // supaya tidak terjadi rantai/redirect-loop.
        Redirect::where('from_path', $to)->delete();
    }

    private function pathFor(Model $model, string $slug): ?string
    {
        return match (true) {
            $model instanceof Post => "/berita/{$slug}",
            $model instanceof Page => "/profil/{$slug}",
            $model instanceof Category => "/berita/kategori/{$slug}",
            $model instanceof Tag => "/berita/tag/{$slug}",
            default => null,
        };
    }
}
