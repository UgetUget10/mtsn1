<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Support\Carbon;

/**
 * Arsip berdasarkan tanggal ala WordPress (wp_get_archives / date archive
 * `/2026/09/`). Daftar bulan yang punya berita terbit + jumlahnya, dan header
 * untuk satu bulan tertentu.
 */
class ArchiveController extends Controller
{
    /**
     * Daftar bulan arsip, terbaru dulu — setara widget "Archives" WP.
     *
     * Agregasi dilakukan di PHP (bukan `strftime`/`DATE_FORMAT` SQL) supaya
     * portabel MySQL ↔ SQLite, sama seperti filter bulan di PostsTable.
     */
    public function index()
    {
        return Post::query()
            ->published()
            ->orderByDesc('published_at')
            ->pluck('published_at')
            ->filter()
            ->groupBy(fn (Carbon $d) => $d->format('Y-m'))
            ->map(fn ($group, string $key) => [
                'year' => (int) substr($key, 0, 4),
                'month' => (int) substr($key, 5, 2),
                'label' => $group->first()->translatedFormat('F Y'),
                'posts_count' => $group->count(),
            ])
            ->values();
    }

    /**
     * Header halaman arsip satu bulan (/berita/arsip/{year}/{month}).
     * 404 bila bulan itu tak punya berita terbit — mencegah URL arsip kosong
     * terindeks (wp: is_404 untuk arsip tanpa post).
     */
    public function show(int $year, int $month)
    {
        abort_unless($year >= 1970 && $year <= 2200 && $month >= 1 && $month <= 12, 404);

        $count = Post::query()
            ->published()
            ->whereYear('published_at', $year)
            ->whereMonth('published_at', $month)
            ->count();

        abort_unless($count > 0, 404);

        $date = Carbon::createFromDate($year, $month, 1);

        return [
            'year' => $year,
            'month' => $month,
            'label' => $date->translatedFormat('F Y'),
            'posts_count' => $count,
        ];
    }
}
