<?php

namespace App\Models;

use App\Models\Concerns\TriggersFrontendRevalidation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class MenuItem extends Model
{
    use HasTranslations;
    use TriggersFrontendRevalidation;

    protected $guarded = [];

    /** @var array<int, string> */
    public array $translatable = ['label'];

    protected $casts = ['is_active' => 'boolean'];

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->where('is_active', true)->orderBy('order');
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function tag(): BelongsTo
    {
        return $this->belongsTo(Tag::class);
    }

    /**
     * URL final item ini. Tipe ber-referensi konten (page/post/category/tag)
     * membangun URL dari slug TERBARU relasi — tak patah saat slug berubah,
     * beda dari `custom_url` yang harfiah. `null` = tautan mati / tanpa tautan.
     */
    public function resolvedUrl(): ?string
    {
        return match ($this->type) {
            'page' => $this->page ? "/profil/{$this->page->slug}" : null,
            'post' => $this->post ? "/berita/{$this->post->slug}" : null,
            'category' => $this->category ? "/berita/kategori/{$this->category->slug}" : null,
            'tag' => $this->tag ? "/berita/tag/{$this->tag->slug}" : null,
            'custom_url' => $this->url,
            default => null,
        };
    }

    /**
     * Apakah item ini layak disajikan ke frontend?
     *
     * Tipe `section` memang sengaja tanpa tautan (hanya pengelompok), jadi
     * tetap sah. Tapi item `page`/`custom_url` yang URL-nya kosong berarti
     * TAUTANNYA MATI — biasanya karena halaman tujuan sudah dihapus. Item
     * seperti itu tidak boleh dikirim: frontend merender `href` apa adanya
     * dan `null` membuat navigasi rusak.
     */
    public function isRenderable(): bool
    {
        if ($this->type === 'section') {
            return true;
        }

        return filled($this->resolvedUrl());
    }
}
