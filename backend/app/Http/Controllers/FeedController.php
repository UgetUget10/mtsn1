<?php

namespace App\Http\Controllers;

use App\Models\Agenda;
use App\Models\Category;
use App\Models\Page;
use App\Models\Post;
use App\Models\Redirect;
use App\Models\Setting;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Endpoint kompatibilitas ala WordPress:
 * - GET /sitemap.xml   (wp-sitemap.xml)
 * - GET /feed          (RSS 2.0, /feed/ WP)
 * - GET /api/v1/resolve?path=...  -> 301 target bila path lama diarahkan
 *
 * URL publik memakai FRONTEND_URL supaya menunjuk ke situs Next.js, bukan API.
 */
class FeedController extends Controller
{
    private function frontend(): string
    {
        return rtrim(config('services.frontend.url') ?? env('FRONTEND_URL', config('app.url')), '/');
    }

    /**
     * Header cache pendek untuk feed publik — CDN/browser boleh menyajikan
     * ulang selama beberapa menit tanpa membebani DB. Nilai `key` sudah
     * mengandung locale sehingga versi id & en tidak saling menimpa.
     */
    private function cacheFeed(string $key, int $ttl, \Closure $build)
    {
        $locale = App::getLocale();
        [$body, $contentType] = Cache::remember("feed:{$key}:{$locale}", $ttl, $build);

        return response($body, 200, [
            'Content-Type' => $contentType,
            'Cache-Control' => "public, max-age={$ttl}, s-maxage={$ttl}",
        ]);
    }

    public function sitemap()
    {
        return $this->cacheFeed('sitemap', 600, function () {
            return [$this->buildSitemap(), 'application/xml; charset=UTF-8'];
        });
    }

    private function buildSitemap(): string
    {
        $base = $this->frontend();
        $urls = [];

        $urls[] = ['loc' => $base.'/', 'lastmod' => now()->toAtomString(), 'priority' => '1.0'];

        foreach (Page::where('is_published', true)
            ->where('slug', '!=', Page::HOMEPAGE_SLUG)
            ->get(['slug', 'updated_at']) as $p) {
            $urls[] = [
                'loc' => $base.'/profil/'.$p->slug,
                'lastmod' => $p->updated_at?->toAtomString(),
                'priority' => '0.7',
            ];
        }

        foreach (Post::published()->get(['slug', 'updated_at']) as $p) {
            $urls[] = [
                'loc' => $base.'/berita/'.$p->slug,
                'lastmod' => $p->updated_at?->toAtomString(),
                'priority' => '0.6',
            ];
        }

        foreach (Category::whereHas('posts', fn ($q) => $q->published())
            ->get(['slug', 'updated_at']) as $c) {
            $urls[] = [
                'loc' => $base.'/berita/kategori/'.$c->slug,
                'lastmod' => $c->updated_at?->toAtomString(),
                'priority' => '0.4',
            ];
        }

        return view('sitemap', ['urls' => $urls])->render();
    }

    /**
     * RSS 2.0. Tanpa parameter = feed situs (/feed). Dengan ?category= / ?tag=
     * / ?author= = feed arsip, meniru WordPress `/category/x/feed/`,
     * `/tag/x/feed/`, `/author/x/feed/`.
     */
    public function feed(Request $request)
    {
        $categorySlug = $request->query('category');
        $tagSlug = $request->query('tag');
        $authorSlug = $request->query('author');

        // Validasi acuan arsip lebih dulu supaya feed untuk slug yang tidak ada
        // membalas 404, bukan feed kosong yang menyesatkan.
        $category = $categorySlug ? Category::where('slug', $categorySlug)->firstOrFail() : null;
        $tag = $tagSlug ? Tag::where('slug', $tagSlug)->firstOrFail() : null;
        $author = $authorSlug ? User::where('slug', $authorSlug)->firstOrFail() : null;

        $cacheKey = match (true) {
            $category !== null => 'rss:category:'.$category->slug,
            $tag !== null => 'rss:tag:'.$tag->slug,
            $author !== null => 'rss:author:'.$author->slug,
            default => 'rss',
        };

        return $this->cacheFeed($cacheKey, 300, function () use ($category, $tag, $author) {
            $base = $this->frontend();

            // wp: Settings → Reading. Default meniru perilaku lama: ringkasan, 20 item.
            $fullText = Setting::get('rss_content_mode') === 'lengkap';
            $limit = max(1, min(50, (int) (Setting::get('posts_per_rss') ?: 20)));

            $query = Post::published()->with('author')->latest('published_at')->limit($limit);
            $siteName = Setting::get('site_name', config('app.name'));

            [$title, $selfPath] = match (true) {
                $category !== null => [
                    "{$siteName} » Kategori: {$category->name}",
                    '/feed?category='.$category->slug,
                ],
                $tag !== null => [
                    "{$siteName} » Tag: {$tag->name}",
                    '/feed?tag='.$tag->slug,
                ],
                $author !== null => [
                    "{$siteName} » Penulis: {$author->name}",
                    '/feed?author='.$author->slug,
                ],
                default => [$siteName, '/feed'],
            };

            if ($category) {
                // Termasuk berita sub-kategori (wp: "include children").
                $slugs = Category::query()->where('slug', $category->slug)
                    ->orWhere('parent_id', $category->id)
                    ->pluck('id');
                $query->where(fn (Builder $q) => $q
                    ->whereIn('category_id', $slugs)
                    ->orWhereHas('categories', fn (Builder $c) => $c->whereIn('categories.id', $slugs)));
            }
            if ($tag) {
                $query->whereHas('tags', fn (Builder $q) => $q->where('tags.id', $tag->id));
            }
            if ($author) {
                $query->where('user_id', $author->id);
            }

            $posts = $query->get();

            $xml = view('feed', [
                'siteName' => $title,
                'siteUrl' => $base,
                'description' => Setting::get('site_tagline', ''),
                'updated' => optional($posts->first())->published_at ?? now(),
                'posts' => $posts,
                'base' => $base,
                'fullText' => $fullText,
                'selfUrl' => $base.$selfPath,
            ])->render();

            return [$xml, 'application/rss+xml; charset=UTF-8'];
        });
    }

    /**
     * Kalender agenda dalam format iCalendar (.ics) — bisa di-"Berlangganan"
     * di Google Calendar / Apple Calendar. Mirip ekspor .ics plugin event WP.
     * Semua agenda mendatang + 90 hari ke belakang.
     */
    public function agendaCalendar()
    {
        return $this->cacheFeed('ics', 900, function () {
            return [$this->buildCalendar(), 'text/calendar; charset=UTF-8'];
        })->header('Content-Disposition', 'inline; filename="agenda.ics"');
    }

    private function buildCalendar(): string
    {
        $events = Agenda::query()
            ->where('start_at', '>=', now()->subDays(90))
            ->orderBy('start_at')
            ->get();

        $host = parse_url(config('app.url'), PHP_URL_HOST) ?: 'mtsn1';
        $siteName = Setting::get('site_name', config('app.name'));
        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//'.$this->ics($siteName).'//Agenda//ID',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'X-WR-CALNAME:'.$this->ics('Agenda '.$siteName),
        ];

        foreach ($events as $e) {
            $start = $e->start_at;
            $end = $e->end_at ?? $e->start_at->copy()->addHour();

            $lines[] = 'BEGIN:VEVENT';
            $lines[] = 'UID:agenda-'.$e->id.'@'.$host;
            $lines[] = 'DTSTAMP:'.$e->updated_at->utc()->format('Ymd\THis\Z');
            $lines[] = 'DTSTART:'.$start->utc()->format('Ymd\THis\Z');
            $lines[] = 'DTEND:'.$end->utc()->format('Ymd\THis\Z');
            $lines[] = 'SUMMARY:'.$this->ics($e->title);
            if ($e->location) {
                $lines[] = 'LOCATION:'.$this->ics($e->location);
            }
            if ($e->description) {
                $lines[] = 'DESCRIPTION:'.$this->ics(Str::of($e->description)->stripTags()->squish());
            }
            $lines[] = 'END:VEVENT';
        }

        $lines[] = 'END:VCALENDAR';

        // RFC 5545: pemisah baris CRLF.
        return implode("\r\n", $lines)."\r\n";
    }

    /** Escape teks sesuai RFC 5545 (koma, titik-koma, backslash, newline). */
    private function ics(string $value): string
    {
        return str_replace(
            ['\\', ',', ';', "\r\n", "\n"],
            ['\\\\', '\,', '\;', '\n', '\n'],
            $value,
        );
    }

    /** Frontend memanggil ini saat kena 404 untuk cek apakah ada redirect lama. */
    public function resolve(Request $request)
    {
        $path = (string) $request->query('path', '');
        $redirect = Redirect::resolve($path);

        abort_if(! $redirect, 404);

        $redirect->increment('hits');
        $redirect->forceFill(['last_hit_at' => now()])->saveQuietly();

        return response()->json([
            'to' => $redirect->to_path,
            'status' => $redirect->status,
        ]);
    }
}
