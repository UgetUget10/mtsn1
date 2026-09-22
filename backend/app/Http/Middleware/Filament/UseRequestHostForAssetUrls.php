<?php

namespace App\Http\Middleware\Filament;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * Panel admin dibuka lewat dua alamat berbeda (domain publik & IP internal
 * LAN, mis. saat DNS domain belum/gagal resolve dari jaringan pengakses).
 * APP_URL selalu tetap satu domain — kalau dibuka lewat alamat lain, semua
 * URL absolut yang dihasilkan Laravel/Filament (terutama link gambar media
 * dari Storage::disk('public')->url()) tetap mengarah ke domain APP_URL,
 * yang bisa saja tidak bisa di-resolve dari jaringan pengakses saat itu.
 *
 * Middleware ini HANYA dipasang di panel Filament (lihat
 * AdminPanelProvider) — bukan global — supaya API publik yang dipakai
 * frontend (lib/media.ts#toOptimizerSrc di Next.js) tetap selalu menerima
 * URL berbasis APP_URL yang stabil/dapat diprediksi.
 */
class UseRequestHostForAssetUrls
{
    public function handle(Request $request, Closure $next): Response
    {
        self::apply($request);

        return $next($request);
    }

    public static function apply(Request $request): void
    {
        $origin = $request->getSchemeAndHttpHost();

        URL::forceRootUrl($origin);
        config(['filesystems.disks.public.url' => $origin.'/storage']);
    }
}
