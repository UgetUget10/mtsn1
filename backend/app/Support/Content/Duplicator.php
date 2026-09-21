<?php

namespace App\Support\Content;

use App\Models\Achievement;
use App\Models\Gallery;
use App\Models\Page;
use App\Models\Post;
use App\Models\Slider;
use App\Models\Teacher;

/**
 * Menggandakan konten — setara plugin "Duplicate Post"/"Yoast Duplicate Post"
 * di WordPress: salinan selalu lahir sebagai DRAFT dengan judul bertanda
 * "(salinan)", supaya tidak pernah tanpa sengaja tayang menggantikan aslinya.
 *
 * Yang sengaja TIDAK ikut disalin:
 *   - slug          → dibuat ulang dari judul baru (slug unik)
 *   - preview_token → token pratinjau harus unik per konten
 *   - views         → statistik milik artikel asli
 *   - published_at  → salinan belum pernah terbit
 *   - password      → kredensial tak boleh menyebar diam-diam
 *   - is_featured   → sorotan adalah keputusan editorial per artikel
 *   - revisi & komentar → riwayat milik artikel asli
 */
class Duplicator
{
    /** Duplikat sebuah berita beserta taksonomi & gambar sampulnya. */
    public static function post(Post $post): Post
    {
        // Record yang datang dari tabel Filament membawa atribut agregat hasil
        // withCount()/counts() (mis. `comments_count`) yang BUKAN kolom tabel.
        // replicate() ikut menyalinnya dan insert-nya gagal — buang dulu.
        $post = self::withoutAggregates($post);

        $copy = $post->replicate([
            'slug',
            'preview_token',
            'views',
            'published_at',
            'password',
            'is_featured',
            'created_at',
            'updated_at',
            'deleted_at',
        ]);

        $copy->title = self::copyTitle($post->getRawOriginal('title'));
        $copy->slug = null;                       // HasSlug bikin ulang dari judul
        $copy->status = Post::STATUS_DRAFT;
        $copy->visibility = Post::VISIBILITY_PUBLIC;
        $copy->published_at = null;
        $copy->views = 0;
        $copy->is_featured = false;
        $copy->save();

        // Taksonomi many-to-many ikut, karena itu bagian dari "kerangka" yang
        // memang ingin dipakai ulang editor.
        $copy->categories()->sync($post->categories()->pluck('categories.id'));
        $copy->tags()->sync($post->tags()->pluck('tags.id'));

        self::copyMedia($post, $copy, 'cover');

        return $copy;
    }

    /**
     * Buang atribut yang tidak punya kolom di tabel (hasil withCount/counts),
     * dengan mengambil ulang record bersih dari database.
     */
    private static function withoutAggregates(mixed $model): mixed
    {
        return $model->newQuery()->findOrFail($model->getKey());
    }

    /** Duplikat sebuah halaman beserta blok kontennya. */
    public static function page(Page $page): Page
    {
        $page = self::withoutAggregates($page);

        $copy = $page->replicate([
            'slug',
            'preview_token',
            'created_at',
            'updated_at',
            'deleted_at',
        ]);

        $copy->title = self::copyTitle($page->getRawOriginal('title'));
        $copy->slug = null;
        $copy->is_published = false;              // wp: salinan selalu draft
        $copy->save();

        return $copy;
    }

    /**
     * Tambahkan penanda "(salinan)" pada judul. Kolom translatable disimpan
     * sebagai JSON {"id": "...", "en": "..."} — beri tanda pada SETIAP locale
     * supaya editor mengenali salinan di tab bahasa mana pun.
     */
    private static function copyTitle(mixed $rawTitle): mixed
    {
        $decoded = is_string($rawTitle) ? json_decode($rawTitle, true) : $rawTitle;

        if (is_array($decoded)) {
            return collect($decoded)
                ->map(fn ($v) => trim((string) $v).' (salinan)')
                ->all();
        }

        return trim((string) $rawTitle).' (salinan)';
    }

    /**
     * Salin berkas media ke salinan baru. Memakai `copy()` bawaan Spatie agar
     * berkas fisiknya digandakan — kalau hanya menyalin baris DB, menghapus
     * artikel asli akan ikut menghapus gambar milik salinan.
     */
    private static function copyMedia(object $from, object $to, string $collection): void
    {
        $media = $from->getFirstMedia($collection);
        if (! $media) {
            return;
        }

        try {
            $media->copy($to, $collection);
        } catch (\Throwable $e) {
            report($e); // gambar gagal disalin tidak boleh menggagalkan duplikasi
        }
    }

    /**
     * Duplikat galeri beserta seluruh itemnya (foto/video) dan sampulnya.
     * Beda dari Post/Page: galeri tidak punya status draft/terbit, jadi
     * "salinan aman" di sini berarti judul ditandai — bukan menyembunyikan
     * status, karena galeri memang selalu tampil bila ada.
     */
    public static function gallery(Gallery $gallery): Gallery
    {
        $gallery = self::withoutAggregates($gallery);

        $copy = $gallery->replicate([
            'slug',
            'created_at',
            'updated_at',
            'deleted_at',
        ]);

        $copy->title = self::copyTitle($gallery->getRawOriginal('title'));
        $copy->slug = null; // HasSlug bikin ulang dari judul
        $copy->save();

        foreach ($gallery->items as $item) {
            $itemCopy = $item->replicate(['created_at', 'updated_at']);
            $itemCopy->gallery_id = $copy->id;
            $itemCopy->save();
            self::copyMedia($item, $itemCopy, 'image');
        }

        self::copyMedia($gallery, $copy, 'cover');

        return $copy;
    }

    /** Duplikat slider promosi beranda. Salinan dinonaktifkan agar tidak dobel tampil. */
    public static function slider(Slider $slider): Slider
    {
        $slider = self::withoutAggregates($slider);

        $copy = $slider->replicate(['created_at', 'updated_at']);
        $copy->title = self::copyTitle($slider->getRawOriginal('title'));
        $copy->is_active = false;
        $copy->save();

        self::copyMedia($slider, $copy, 'image');

        return $copy;
    }

    /**
     * Duplikat data guru/tendik — berguna sebagai kerangka saat menambah staf
     * baru dengan posisi/mapel yang sama. NIP TIDAK ikut disalin (identitas
     * unik per orang, menyalinnya akan membuat dua baris dengan NIP sama).
     */
    public static function teacher(Teacher $teacher): Teacher
    {
        $teacher = self::withoutAggregates($teacher);

        $copy = $teacher->replicate(['created_at', 'updated_at']);
        $copy->name = self::copyTitle($teacher->name);
        $copy->nip = null;
        $copy->is_active = false;
        $copy->save();

        self::copyMedia($teacher, $copy, 'photo');

        return $copy;
    }

    /** Duplikat entri prestasi — kerangka cepat untuk mencatat prestasi serupa (lomba tahun berikutnya, dst). */
    public static function achievement(Achievement $achievement): Achievement
    {
        $achievement = self::withoutAggregates($achievement);

        $copy = $achievement->replicate(['created_at', 'updated_at', 'deleted_at']);
        $copy->title = self::copyTitle($achievement->getRawOriginal('title'));
        $copy->save();

        self::copyMedia($achievement, $copy, 'image');

        return $copy;
    }
}
