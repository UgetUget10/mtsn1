<?php

namespace App\Support\Revalidation;

use App\Models\Achievement;
use App\Models\Agenda;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Document;
use App\Models\Extracurricular;
use App\Models\Gallery;
use App\Models\GalleryItem;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Post;
use App\Models\ReusableBlock;
use App\Models\Setting;
use App\Models\Slider;
use App\Models\Tag;
use App\Models\Teacher;
use App\Models\Testimonial;
use App\Models\User;
use App\Models\WidgetArea;
use Illuminate\Database\Eloquent\Model;

/**
 * Menerjemahkan sebuah model yang berubah menjadi daftar cache tag & path
 * frontend yang perlu disegarkan — meniru cara WordPress mem-purge hanya
 * URL yang terpengaruh (post + arsip + beranda), bukan seluruh situs.
 *
 * Tag harus cocok dengan yang dipasang di frontend/src/lib/api.ts
 * (opsi `next: { tags: [...] }` pada setiap fetch).
 */
class RevalidationTargets
{
    /**
     * @return array{tags: array<int, string>, paths: array<int, string>}
     */
    public static function for(Model $model): array
    {
        return match (true) {
            $model instanceof Post => self::post($model),
            $model instanceof Comment => self::comment($model),
            $model instanceof Page => self::page($model),
            $model instanceof Category => ['tags' => ['posts', 'menu'], 'paths' => ['/berita']],
            $model instanceof Tag => self::tag($model),
            // `/profil` membangun daftarnya dari menu "Profil" (bukan lagi dari
            // seluruh halaman CMS), jadi susunan menu yang berubah harus ikut
            // menyegarkan halaman itu — tag `menu` saja tidak cukup karena
            // halaman tersebut ISR dengan path sendiri.
            $model instanceof Menu,
            $model instanceof MenuItem => ['tags' => ['menu'], 'paths' => ['/profil']],
            // `posts_per_page` (wp: Settings → Reading) ikut menentukan isi
            // setiap arsip berita, jadi tag `posts` HARUS ikut dipurge —
            // tanpa itu jumlah item per halaman tidak pernah berubah di publik.
            $model instanceof Setting => ['tags' => ['settings', 'menu', 'posts'], 'paths' => []],
            $model instanceof Slider => ['tags' => ['sliders'], 'paths' => ['/']],
            $model instanceof Teacher => ['tags' => ['teachers'], 'paths' => ['/guru']],
            $model instanceof Agenda => ['tags' => ['agendas'], 'paths' => ['/agenda', '/']],
            $model instanceof Gallery,
            $model instanceof GalleryItem => ['tags' => ['galleries'], 'paths' => ['/galeri']],
            $model instanceof Document => ['tags' => ['documents'], 'paths' => ['/dokumen']],
            $model instanceof Achievement => ['tags' => ['achievements'], 'paths' => ['/prestasi']],
            // Testimoni hanya tampil di beranda (section "testimoni") — tidak
            // punya halaman arsip sendiri.
            $model instanceof Testimonial => ['tags' => ['testimonials'], 'paths' => ['/']],
            $model instanceof Extracurricular => ['tags' => ['extracurriculars'], 'paths' => ['/ekstrakurikuler']],
            // ReusableBlock dipakai baik di halaman biasa (blok tipe `reusable`)
            // maupun sebagai isi widget area — purge dua-duanya, tidak tahu
            // dari sisi ini yang mana yang memakainya.
            $model instanceof ReusableBlock => ['tags' => ['pages', 'widget-areas'], 'paths' => []],
            $model instanceof WidgetArea => ['tags' => ['widget-areas'], 'paths' => []],
            $model instanceof User => self::author($model),
            default => ['tags' => [], 'paths' => []],
        };
    }

    /**
     * @return array{tags: array<int, string>, paths: array<int, string>}
     */
    private static function post(Post $post): array
    {
        $paths = ['/berita', '/'];

        // Saat create, getRawOriginal('slug') masih null — pakai nilai saat ini.
        // Saat slug diubah, pakai slug LAMA agar URL lama ikut disegarkan
        // (redirect 301 baru juga dibuat SlugHistoryObserver).
        foreach ([$post->getRawOriginal('slug'), $post->slug] as $slug) {
            if ($slug) {
                $paths[] = "/berita/{$slug}";
            }
        }

        // Arsip tanggal (wp: date archive) — jumlah & isi bulan berubah saat
        // post terbit / tanggalnya digeser. Pakai tanggal LAMA dan BARU.
        foreach ([$post->getRawOriginal('published_at'), $post->published_at] as $raw) {
            if (! $raw) {
                continue;
            }
            try {
                $date = $raw instanceof \DateTimeInterface ? $raw : new \DateTimeImmutable((string) $raw);
            } catch (\Throwable) {
                continue;
            }
            $paths[] = '/berita/arsip/'.$date->format('Y').'/'.$date->format('m');
        }

        // `menu` ikut: sebuah MenuItem bisa mereferensi post ini (wp: Post
        // sebagai item menu) — href-nya dibangun dari slug, jadi rename slug
        // harus mem-purge menu yang di-cache.
        return ['tags' => ['posts', 'archives', 'menu'], 'paths' => array_values(array_unique($paths))];
    }

    /**
     * Komentar disegarkan bersama artikel induknya (path + tag `posts`).
     * Frontend menampilkan jumlah & daftar komentar yang disetujui pada halaman
     * berita, jadi moderasi di panel harus mem-purge URL artikel itu.
     *
     * @return array{tags: array<int, string>, paths: array<int, string>}
     */
    private static function comment(Comment $comment): array
    {
        $parent = $comment->commentable;

        if ($parent instanceof Post) {
            return self::post($parent);
        }

        if ($parent instanceof Page) {
            return self::page($parent);
        }

        return ['tags' => ['posts'], 'paths' => ['/berita']];
    }

    /**
     * Tag berubah → segarkan daftar berita + arsip tag (slug lama & baru bila
     * di-rename, mengiringi redirect 301 dari SlugHistoryObserver).
     *
     * @return array{tags: array<int, string>, paths: array<int, string>}
     */
    private static function tag(Tag $tag): array
    {
        $paths = ['/berita'];
        foreach ([$tag->getRawOriginal('slug'), $tag->slug] as $slug) {
            if ($slug) {
                $paths[] = "/berita/tag/{$slug}";
            }
        }

        return ['tags' => ['posts', 'tags', 'menu'], 'paths' => array_values(array_unique($paths))];
    }

    /**
     * Profil penulis berubah → segarkan arsip penulis + daftar berita
     * (byline memuat nama/avatar). Purge lebih hemat daripada seluruh situs.
     *
     * @return array{tags: array<int, string>, paths: array<int, string>}
     */
    private static function author(User $user): array
    {
        $paths = ['/berita'];
        if ($user->slug) {
            $paths[] = "/penulis/{$user->slug}";
        }

        return ['tags' => ['posts', 'authors'], 'paths' => $paths];
    }

    /**
     * @return array{tags: array<int, string>, paths: array<int, string>}
     */
    private static function page(Page $page): array
    {
        $current = $page->slug;
        $original = $page->getRawOriginal('slug') ?: $current;

        if ($current === Page::HOMEPAGE_SLUG || $original === Page::HOMEPAGE_SLUG) {
            return ['tags' => ['pages', 'settings'], 'paths' => ['/']];
        }

        // Kartu "Program Unggulan" di beranda membaca blok card_grid halaman
        // ini (lihat frontend/src/app/[locale]/page.tsx) — purge `/` juga,
        // atau perubahan di panel admin tak pernah sampai ke beranda.
        $paths = in_array($current, ['program-unggulan'], true)
            || in_array($original, ['program-unggulan'], true)
            ? ['/']
            : [];

        $paths[] = '/profil';
        foreach ([$original, $current] as $slug) {
            if ($slug) {
                $paths[] = "/profil/{$slug}";
            }
        }

        // Halaman induk & anak menampilkan sub-navigasi/breadcrumb yang memuat
        // judul halaman ini — segarkan juga (wp: hierarchical page cache).
        if ($page->parent?->slug) {
            $paths[] = "/profil/{$page->parent->slug}";
        }
        foreach ($page->children as $child) {
            $paths[] = "/profil/{$child->slug}";
        }

        return ['tags' => ['pages'], 'paths' => array_values(array_unique($paths))];
    }
}
