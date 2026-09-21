<?php

namespace App\Models;

use App\Models\Concerns\TriggersFrontendRevalidation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Spatie\Translatable\HasTranslations;

/**
 * Tag — taksonomi non-hierarkis ala WordPress `post_tag`.
 */
class Tag extends Model
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
            ->logOnly(['name', 'slug'])
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

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class);
    }
}
