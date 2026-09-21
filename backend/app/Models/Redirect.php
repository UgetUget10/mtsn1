<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Pemetaan URL lama -> baru. Dibuat otomatis oleh App\Observers\SlugHistoryObserver
 * saat slug Post/Page/Category/Tag berubah — meniru perilaku core WordPress yang
 * menyimpan `_wp_old_slug` dan me-redirect 301 permalink lama.
 */
class Redirect extends Model
{
    protected $guarded = [];

    protected static function booted(): void
    {
        // Redirect 301 ditangani frontend di middleware (proxy.ts) yang men-cache
        // hasil /resolve dengan tag "redirects". Setiap perubahan baris redirect
        // harus mem-purge tag itu agar slug lama langsung dialihkan.
        $purge = function (): void {
            $url = config('services.frontend.revalidate_url');
            $secret = config('services.frontend.revalidate_secret');
            if (! $url || ! $secret || app()->runningInConsole()) {
                return;
            }
            try {
                Http::timeout(3)->acceptJson()->post($url, [
                    'secret' => $secret,
                    'tags' => ['redirects'],
                    'paths' => [],
                ]);
            } catch (\Throwable $e) {
                Log::warning('Purge redirects gagal: '.$e->getMessage());
            }
        };

        static::saved($purge);
        static::deleted($purge);
    }

    protected $casts = [
        'status' => 'integer',
        'hits' => 'integer',
        'last_hit_at' => 'datetime',
    ];

    public static function resolve(string $path): ?self
    {
        return static::query()->where('from_path', '/'.ltrim($path, '/'))->first();
    }

    /**
     * Apakah `to_path` masih menunjuk konten yang benar-benar ada?
     *
     * Redirect yang tujuannya sudah mati menghasilkan 301 → 404 ("redirect ke
     * nowhere") yang merugikan SEO. WordPress tak punya pemeriksaan ini, tapi
     * plugin redirect populer (mis. Redirection) menandainya, dan panel kita
     * menampilkannya di kolom "Tujuan" RedirectResource.
     */
    public function targetExists(): bool
    {
        $path = '/'.ltrim((string) $this->to_path, '/');
        $slug = basename($path);

        if ($slug === '' || $slug === '/') {
            return true; // menunjuk ke root / indeks — selalu sah
        }

        return match (true) {
            str_starts_with($path, '/berita/kategori/') => Category::where('slug', $slug)->exists(),
            str_starts_with($path, '/berita/tag/') => Tag::where('slug', $slug)->exists(),
            str_starts_with($path, '/berita/') => Post::where('slug', $slug)->exists(),
            str_starts_with($path, '/profil/') => Page::where('slug', $slug)->exists(),
            default => true, // pola tak dikenal — jangan menuduh
        };
    }
}
