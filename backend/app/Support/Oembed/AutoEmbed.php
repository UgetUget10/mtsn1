<?php

namespace App\Support\Oembed;

use Illuminate\Support\Str;

/**
 * Auto-embed ala WordPress: sebuah URL yang berdiri sendiri di satu baris /
 * paragraf diubah menjadi sematan responsif (iframe YouTube/Vimeo, blockquote
 * Instagram/X, dll.) saat konten diserialisasi ke API.
 *
 * Tidak memanggil endpoint oEmbed eksternal — pemetaan URL → markup dilakukan
 * lokal untuk penyedia populer (sama seperti daftar putih WP-core), sehingga
 * tetap cepat, tanpa kunci API, dan aman dari SSRF.
 */
class AutoEmbed
{
    /**
     * Terapkan auto-embed pada string HTML rich text.
     *
     * Menangani dua bentuk URL "telanjang":
     *  - dibungkus paragraf sendiri:  <p>https://youtu.be/xxxx</p>
     *  - anchor yang teks & href-nya sama di paragraf sendiri (perilaku editor).
     */
    public static function html(?string $html): ?string
    {
        if (! filled($html) || ! Str::contains($html, 'http')) {
            return $html;
        }

        // <p><a href="URL">URL</a></p>  → <p>URL</p>  (samakan ke bentuk telanjang)
        $html = preg_replace_callback(
            '#<p>\s*<a[^>]+href="([^"]+)"[^>]*>\s*\1\s*</a>\s*</p>#i',
            fn ($m) => '<p>'.$m[1].'</p>',
            $html,
        ) ?? $html;

        // <p>URL</p> (hanya berisi satu URL) → markup sematan
        return preg_replace_callback(
            '#<p>\s*(https?://[^\s<]+?)\s*</p>#i',
            function (array $m): string {
                $embed = self::provider(html_entity_decode($m[1]));

                return $embed ?? $m[0];
            },
            $html,
        ) ?? $html;
    }

    /**
     * Petakan satu URL ke markup sematan, atau null bila penyedia tak dikenal.
     */
    public static function provider(string $url): ?string
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $host = Str::of($host)->after('www.')->value();

        return match (true) {
            $host === 'youtube.com', $host === 'm.youtube.com', $host === 'youtu.be'
                => self::youtube($url),
            $host === 'vimeo.com', $host === 'player.vimeo.com'
                => self::vimeo($url),
            str_ends_with($host, 'google.com') && str_contains($url, '/maps')
                => self::iframe($url, '16 / 9', 'Peta'),
            $host === 'open.spotify.com'
                => self::spotify($url),
            $host === 'instagram.com'
                => self::instagram($url),
            $host === 'twitter.com', $host === 'x.com'
                => self::tweet($url),
            $host === 'tiktok.com'
                => self::tiktok($url),
            default => null,
        };
    }

    private static function youtube(string $url): ?string
    {
        $id = null;
        if (preg_match('#youtu\.be/([\w-]{6,})#', $url, $m)) {
            $id = $m[1];
        } elseif (preg_match('#[?&]v=([\w-]{6,})#', $url, $m)) {
            $id = $m[1];
        } elseif (preg_match('#/(embed|shorts)/([\w-]{6,})#', $url, $m)) {
            $id = $m[2];
        }

        if (! $id) {
            return null;
        }

        return self::iframe(
            'https://www.youtube-nocookie.com/embed/'.$id,
            '16 / 9',
            'Video YouTube',
            allow: 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share',
        );
    }

    private static function vimeo(string $url): ?string
    {
        if (! preg_match('#vimeo\.com/(?:video/)?(\d+)#', $url, $m)) {
            return null;
        }

        return self::iframe(
            'https://player.vimeo.com/video/'.$m[1],
            '16 / 9',
            'Video Vimeo',
            allow: 'autoplay; fullscreen; picture-in-picture',
        );
    }

    private static function spotify(string $url): ?string
    {
        $path = (string) parse_url($url, PHP_URL_PATH);
        if (! preg_match('#^/(track|album|playlist|episode|show)/([\w]+)#', $path, $m)) {
            return null;
        }
        $tall = in_array($m[1], ['album', 'playlist', 'show'], true);

        return self::iframe(
            "https://open.spotify.com/embed/{$m[1]}/{$m[2]}",
            $tall ? '1 / 1' : '5 / 2',
            'Pemutar Spotify',
            allow: 'autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture',
        );
    }

    private static function instagram(string $url): string
    {
        $url = strtok($url, '?');

        return '<blockquote class="instagram-media" data-instgrm-permalink="'.e($url).'" data-instgrm-version="14">'
            .'<a href="'.e($url).'">Lihat di Instagram</a></blockquote>'
            .'<script async src="https://www.instagram.com/embed.js"></script>';
    }

    private static function tweet(string $url): string
    {
        return '<blockquote class="twitter-tweet"><a href="'.e($url).'">Lihat postingan</a></blockquote>'
            .'<script async src="https://platform.twitter.com/widgets.js" charset="utf-8"></script>';
    }

    private static function tiktok(string $url): ?string
    {
        if (! preg_match('#/video/(\d+)#', $url, $m)) {
            return null;
        }

        return '<blockquote class="tiktok-embed" cite="'.e($url).'" data-video-id="'.$m[1].'">'
            .'<a href="'.e($url).'">Lihat di TikTok</a></blockquote>'
            .'<script async src="https://www.tiktok.com/embed.js"></script>';
    }

    /**
     * Bungkus iframe dalam wrapper rasio-aspek agar responsif di frontend
     * (kelas `.oembed` diberi gaya di prose-content).
     */
    private static function iframe(
        string $src,
        string $ratio,
        string $title,
        string $allow = 'fullscreen',
    ): string {
        return '<div class="oembed" style="aspect-ratio:'.$ratio.'">'
            .'<iframe src="'.e($src).'" title="'.e($title).'" loading="lazy" '
            .'allow="'.e($allow).'" allowfullscreen referrerpolicy="strict-origin-when-cross-origin" '
            .'frameborder="0"></iframe></div>';
    }
}
