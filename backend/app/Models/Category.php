<?php

namespace App\Models;

use App\Models\Concerns\TriggersFrontendRevalidation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Spatie\Translatable\HasTranslations;

class Category extends Model
{
    use HasSlug;
    use HasTranslations;
    use LogsActivity;
    use TriggersFrontendRevalidation;

    protected $guarded = [];

    /** @var array<int, string> */
    public array $translatable = ['name'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'slug', 'parent_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()->generateSlugsFrom('name')->saveSlugsTo('slug');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** Kategori induk (wp: term parent). Null = kategori tingkat atas. */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** Sub-kategori langsung. */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /** Rantai leluhur dari akar → induk langsung (untuk breadcrumb). */
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

    /** Kategori utama (kolom category_id) — relasi historis. */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    /** Semua post yang tergabung lewat pivot many-to-many (wp: multi-kategori). */
    public function taggedPosts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class);
    }
}
