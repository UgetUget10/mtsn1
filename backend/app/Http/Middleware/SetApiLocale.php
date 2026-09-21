<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Set locale aplikasi dari query string `?locale=id|en` untuk request API.
 * Model translatable (Post, Page, Category, MenuItem — lihat masing-masing
 * HasTranslations) otomatis mengikuti App::getLocale() saat diserialisasi
 * lewat accessor, jadi middleware ini cukup dijalankan sebelum controller.
 *
 * Locale tak dikenal (di luar config('translatable.locales')) diabaikan —
 * request tetap jalan dengan locale default (APP_LOCALE) daripada 404/500.
 */
class SetApiLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->query('locale');
        $available = array_keys(config('translatable.locales', []));

        if ($locale && in_array($locale, $available, true)) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
