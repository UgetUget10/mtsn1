<?php

namespace App\Http\Resources;

use App\Support\Oembed\AutoEmbed;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class PostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $detail = $request->routeIs('*.show');

        // Post terlindungi kata sandi: isi hanya dibuka bila pengunjung sudah
        // meng-unlock (cookie/token diverifikasi PostController). Di daftar,
        // selalu terkunci.
        $protected = $this->resource->isPasswordProtected();
        $unlocked = $protected && $request->attributes->get('post_unlocked') === true;

        // Metadata gambar sampul (wp: Alt / Caption / Photo credit di Media).
        // Diambil sekali supaya tidak query media berkali-kali.
        $cover = $this->getFirstMedia('cover');

        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'protected' => $protected,
            'unlocked' => $protected ? $unlocked : true,
            // Ringkasan ala WordPress: manual bila diisi, jika tidak dipotong
            // otomatis dari body (hormati <!--more-->), selalu teks polos.
            'excerpt' => $this->resource->displayExcerpt(),
            'cover' => $this->coverUrl(),
            'cover_alt' => $cover?->getCustomProperty('alt'),
            'cover_caption' => $cover?->getCustomProperty('caption'),
            'cover_credit' => $cover?->getCustomProperty('credit'),
            'is_featured' => $this->is_featured,
            'views' => $this->views,
            'published_at' => $this->published_at?->toIso8601String(),
            // Tanggal ubah terakhir (wp: post_modified) — dipakai frontend untuk
            // "Terakhir diperbarui" + schema.org dateModified. Hanya dikirim bila
            // memang berbeda dari tanggal terbit, supaya artikel yang belum
            // pernah disunting tidak tampil seolah baru diubah.
            'updated_at' => $this->wasEditedAfterPublish()
                ? $this->updated_at?->toIso8601String()
                : null,
            'category' => $this->whenLoaded('category', fn () => [
                'name' => $this->category?->name,
                'slug' => $this->category?->slug,
            ]),
            'author' => $this->whenLoaded('author', fn () => $this->author ? [
                'name' => $this->author->name,
                'slug' => $this->author->isPublicAuthor() ? $this->author->slug : null,
                'avatar' => $this->author->avatarUrl(),
                'job_title' => $this->author->job_title,
            ] : null),
            'tags' => $this->whenLoaded('tags', fn () => $this->tags->map(fn ($t) => [
                'name' => $t->name,
                'slug' => $t->slug,
            ])),
            'seo' => $this->seo(),
            'comments_count' => $this->when(
                $this->comments_count !== null || $detail,
                fn () => (int) ($this->comments_count ?? $this->comments()->approved()->count()),
            ),
            $this->mergeWhen($detail, [
                // Sembunyikan isi bila terproteksi & belum di-unlock (wp: the_content
                // menampilkan form password, bukan konten).
                // Auto-embed ala WordPress: URL YouTube/Vimeo/dll. yang berdiri
                // sendiri di satu paragraf diubah jadi sematan responsif.
                'body' => ($protected && ! $unlocked) ? null : $this->renderBody(),
                'comments_open' => $this->resource->commentsAreOpen(),
                // Navigasi artikel bersebelahan (wp: previous_post_link / next_post_link).
                'adjacent' => [
                    'previous' => $this->adjacentRef($this->resource->previousPost()),
                    'next' => $this->adjacentRef($this->resource->nextPost()),
                ],
                'custom_fields' => $this->customFields(),
            ]),
        ];
    }

    /**
     * Ringkasan minimal artikel tetangga untuk tautan "Sebelumnya/Berikutnya".
     *
     * @return array<string, string>|null
     */
    /**
     * Isi artikel untuk tampilan tunggal: buang quicktag <!--more--> (di WP,
     * pada single view konten mengalir utuh; teaser hanya untuk daftar) lalu
     * jalankan auto-embed.
     */
    private function renderBody(): ?string
    {
        $body = preg_replace('/<!--\s*more(?:\s+.*?)?\s*-->/is', '', (string) $this->body);

        return AutoEmbed::html($body);
    }

    private function adjacentRef(?\App\Models\Post $post): ?array
    {
        return $post ? ['title' => $post->title, 'slug' => $post->slug] : null;
    }

    /**
     * "Custom Fields" ala WordPress — pasangan kunci/nilai bebas dari
     * meta.custom (lihat Schemas\PostForm), dikirim sebagai objek datar
     * {kunci: nilai} supaya mudah dipakai di frontend. Baris tanpa kunci
     * (belum diisi editor) dibuang.
     *
     * @return array<string, string>
     */
    private function customFields(): array
    {
        $rows = $this->meta['custom'] ?? [];
        if (! is_array($rows)) {
            return [];
        }

        $fields = [];
        foreach ($rows as $row) {
            $key = trim((string) ($row['key'] ?? ''));
            if ($key === '') {
                continue;
            }
            $fields[$key] = (string) ($row['value'] ?? '');
        }

        return $fields;
    }

    /**
     * Blok SEO ala Yoast/RankMath — nilai dari kolom `meta` bila diisi editor,
     * jatuh balik ke judul/excerpt bawaan supaya frontend selalu punya tag lengkap.
     */
    private function seo(): array
    {
        $meta = $this->meta ?? [];

        return [
            'title' => $meta['seo_title'] ?? $this->title,
            'description' => $meta['seo_description'] ?? $this->resource->displayExcerpt(),
            'canonical' => $meta['canonical'] ?? null,
            'noindex' => (bool) ($meta['noindex'] ?? false),
            // og_image disimpan FileUpload sebagai path relatif (mis.
            // "seo/abc.jpg") — harus diubah jadi URL absolut, kalau tidak
            // frontend memancarkan <meta og:image> yang rusak.
            'og_image' => $this->storagePathToUrl($meta['og_image'] ?? null) ?? $this->coverUrl(),
        ];
    }

    /**
     * Utamakan media library (dengan conversion 'card'); jatuh balik ke kolom
     * `cover` string lama selama belum semua data dimigrasi (lihat
     * App\Console\Commands\MigrateLegacyMedia).
     */
    /**
     * Ubah path relatif hasil FileUpload jadi URL absolut. URL penuh (http…)
     * diteruskan apa adanya supaya editor tetap bisa menempel tautan luar.
     */
    private function storagePathToUrl(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }
        if (str_starts_with($path, 'http')) {
            return $path;
        }

        $path = ltrim($path, '/');

        return Storage::disk('public')->exists($path) ? asset('storage/'.$path) : null;
    }

    private function coverUrl(): ?string
    {
        $media = $this->getFirstMedia('cover');
        if ($media) {
            return $media->hasGeneratedConversion('card') ? $media->getUrl('card') : $media->getUrl();
        }

        if (! $this->cover) {
            return null;
        }

        return Storage::disk('public')->exists($this->cover) ? asset('storage/'.$this->cover) : null;
    }
}
