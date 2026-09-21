<?php

namespace App\Models;

use App\Models\Concerns\TriggersFrontendRevalidation;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

/**
 * Blok konten yang bisa dipakai ulang di banyak halaman — setara
 * "Synced Pattern" / Reusable Block WordPress. Halaman merujuknya lewat
 * block bertipe `reusable` dengan `data.slug`, dan PageResource meng-expand
 * isinya saat serialisasi.
 */
class ReusableBlock extends Model
{
    use HasSlug;
    use LogsActivity;
    use TriggersFrontendRevalidation;

    protected $table = 'blocks';

    protected $guarded = [];

    protected $casts = [
        'content' => 'array',
        'is_active' => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'slug', 'is_active'])
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
}
