<?php

namespace App\Models\Concerns;

use App\Models\Agenda;
use App\Models\Category;
use App\Models\Page;
use App\Models\Post;
use App\Models\Setting;
use App\Support\Revalidation\RevalidationTargets;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Setiap kali model konten disimpan/dihapus, beri tahu frontend Next.js untuk
 * menyegarkan hanya cache tag & path yang terpengaruh — bukan seluruh situs.
 * Setara hook `clean_post_cache` / purge per-URL di WordPress.
 *
 * Payload dikirim sebagai JSON POST ke REVALIDATE_URL:
 *   { "secret": "...", "tags": ["posts"], "paths": ["/berita/slug", "/berita", "/"] }
 *
 * Sekaligus mem-bust cache feed backend (sitemap/RSS/iCal) yang relevan
 * — lihat App\Http\Controllers\FeedController::cacheFeed().
 */
trait TriggersFrontendRevalidation
{
    public static function bootTriggersFrontendRevalidation(): void
    {
        $handle = function ($model): void {
            // Lewati bila HANYA kolom yang diabaikan (mis. remember_token saat
            // login) yang berubah — jangan bikin frontend re-render karenanya.
            $ignored = property_exists($model, 'revalidationIgnoredAttributes')
                ? $model::$revalidationIgnoredAttributes
                : [];
            if ($ignored && $model->wasChanged() && ! array_diff(array_keys($model->getChanges()), $ignored)) {
                return;
            }

            static::bustFeedCache($model);

            $url = config('services.frontend.revalidate_url');
            $secret = config('services.frontend.revalidate_secret');

            if (! $url || ! $secret || app()->runningInConsole()) {
                return;
            }

            $targets = RevalidationTargets::for($model);

            try {
                Http::timeout(3)->acceptJson()->post($url, [
                    'secret' => $secret,
                    'tags' => array_values(array_unique($targets['tags'])),
                    'paths' => array_values(array_unique($targets['paths'])),
                ]);
            } catch (\Throwable $e) {
                Log::warning('Frontend revalidate gagal: '.$e->getMessage());
            }
        };

        static::saved($handle);
        static::deleted($handle);
    }

    /** Buang entri cache feed backend yang bergantung pada tipe model ini. */
    protected static function bustFeedCache($model): void
    {
        $feeds = match (true) {
            $model instanceof Post => ['rss', 'sitemap'],
            $model instanceof Page,
            $model instanceof Category => ['sitemap'],
            $model instanceof Agenda => ['ics'],
            $model instanceof Setting => ['rss', 'ics'],
            default => [],
        };

        foreach ($feeds as $feed) {
            foreach (array_keys(config('translatable.locales', ['id' => null])) as $locale) {
                Cache::forget("feed:{$feed}:{$locale}");
            }
        }

        // Feed arsip per kategori/tag/penulis (wp: /category/x/feed/ dll).
        // Slug-nya dinamis, jadi buang yang terkait post ini secara eksplisit.
        if ($model instanceof Post) {
            $keys = [];
            foreach ($model->categories as $c) {
                $keys[] = "rss:category:{$c->slug}";
            }
            if ($model->category) {
                $keys[] = "rss:category:{$model->category->slug}";
            }
            foreach ($model->tags as $t) {
                $keys[] = "rss:tag:{$t->slug}";
            }
            if ($model->author?->slug) {
                $keys[] = "rss:author:{$model->author->slug}";
            }

            foreach (array_unique($keys) as $key) {
                foreach (array_keys(config('translatable.locales', ['id' => null])) as $locale) {
                    Cache::forget("feed:{$key}:{$locale}");
                }
            }
        }
    }
}
