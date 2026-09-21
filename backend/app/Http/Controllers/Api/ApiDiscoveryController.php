<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

/**
 * Dokumen penemuan (discovery) ala `wp-json/` WordPress — daftar endpoint
 * yang tersedia di API publik ini, supaya klien pihak ketiga (mis. aplikasi
 * mobile yang dibuat dengan token dari Profil Saya → Token API) bisa tahu
 * apa yang bisa diakses tanpa membaca kode sumber.
 */
class ApiDiscoveryController extends Controller
{
    public function index()
    {
        $base = url('/api/v1');

        return response()->json([
            'name' => config('app.name'),
            'description' => 'API publik MTsN 1 Kota Malang — konten dibaca tanpa autentikasi; endpoint yang mengubah data (komentar, kontak) dibatasi laju (rate-limited).',
            'routes' => [
                'posts' => "{$base}/posts",
                'post' => "{$base}/posts/{slug}",
                'pages' => "{$base}/pages",
                'page' => "{$base}/pages/{slug}",
                'categories' => "{$base}/categories",
                'tags' => "{$base}/tags",
                'authors' => "{$base}/authors",
                'archives' => "{$base}/archives",
                'search' => "{$base}/search?q=...",
                'menus' => "{$base}/menus/{key}",
                'widget_areas' => "{$base}/widget-areas/{key}",
                'sliders' => "{$base}/sliders",
                'teachers' => "{$base}/teachers",
                'agendas' => "{$base}/agendas",
                'galleries' => "{$base}/galleries",
                'documents' => "{$base}/documents",
                'achievements' => "{$base}/achievements",
                'testimonials' => "{$base}/testimonials",
                'extracurriculars' => "{$base}/extracurriculars",
                'settings' => "{$base}/settings",
            ],
        ]);
    }
}
