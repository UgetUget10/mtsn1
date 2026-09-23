<?php

namespace App\Models;

use App\Models\Concerns\HasRevisions;
use App\Models\Concerns\TriggersFrontendRevalidation;
use App\Support\Blocks\BlockTypes;
use App\Support\Blocks\TreeNormalizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Spatie\Translatable\HasTranslations;

class Page extends Model
{
    use HasRevisions;
    use HasSlug;
    use HasTranslations;
    use LogsActivity;
    use SoftDeletes;
    use TriggersFrontendRevalidation;

    /** Slug singleton dipakai HomepageBuilder untuk mengelola section beranda. */
    public const HOMEPAGE_SLUG = '__homepage__';

    /**
     * Template tata letak ala WordPress (Page Attributes → Template).
     * Kunci = nilai tersimpan; nilai = label yang tampil di admin.
     *
     * @return array<string, string>
     */
    public static function templateOptions(): array
    {
        return [
            'default' => 'Default (dengan daftar isi)',
            'full-width' => 'Lebar penuh (tanpa sidebar)',
            'sidebar-nav' => 'Navigasi sub-halaman (sidebar menu)',
            'landing' => 'Halaman arahan (tanpa header & breadcrumb)',
        ];
    }

    /** Template yang valid; nilai asing dipetakan kembali ke `default`. */
    public function resolvedTemplate(): string
    {
        return array_key_exists($this->template, static::templateOptions())
            ? $this->template
            : 'default';
    }

    protected $guarded = [];

    /** @var array<int, string> */
    public array $translatable = ['title', 'meta_description'];

    /** Kolom yang disnapshot ke tabel revisions. */
    protected array $revisionable = ['title', 'body', 'blocks', 'meta_description', 'template'];

    protected $casts = [
        'is_published' => 'boolean',
        'blocks' => 'array',
        'blocks_draft' => 'array',
        'meta' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (Page $page): void {
            $page->preview_token ??= (string) Str::uuid();
        });
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()->generateSlugsFrom('title')->saveSlugsTo('slug');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'is_published', 'template', 'parent_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /** Halaman induk (wp: Page Attributes → Parent). Null = tingkat atas. */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** Sub-halaman langsung, terurut. */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('order');
    }

    /** Rantai leluhur akar → induk langsung (untuk breadcrumb). */
    public function ancestors(): Collection
    {
        $chain = collect();
        $node = $this->parent;
        while ($node) {
            $chain->prepend($node);
            $node = $node->parent;
        }

        return $chain;
    }

    /**
     * Block yang tampil, terurut, dan sudah dinormalisasi — jika kolom `blocks`
     * kosong tapi `body` (rich text lama) terisi, bungkus otomatis jadi satu
     * block rich_text supaya konten lama tidak pernah hilang dari tampilan.
     */
    public function visibleBlocks(): array
    {
        $blocks = $this->blocks ?? [];

        if (empty($blocks) && filled($this->body)) {
            $blocks = [[
                'type' => BlockTypes::RICH_TEXT,
                'data' => ['heading' => null, 'body' => $this->body],
            ]];
        }

        return collect($blocks)
            ->filter(fn ($b) => ($b['is_visible'] ?? true) === true)
            ->values()
            ->all();
    }

    /**
     * Struktur tree kanvas visual (App\Filament\Pages\PageCanvasEditor), untuk
     * disunting — draf jika ada, jika tidak dinormalisasi dari `blocks` yang
     * published (lihat TreeNormalizer). Tidak pernah menulis ke DB di sini.
     *
     * @return array{schema: int, tree: array<int, array<string, mixed>>}
     */
    public function visibleTree(): array
    {
        if (! empty($this->blocks_draft)) {
            return TreeNormalizer::normalize($this->blocks_draft);
        }

        return TreeNormalizer::normalize($this->visibleBlocks());
    }
}
