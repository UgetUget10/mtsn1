<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

/**
 * Template section kanvas visual — setara "Save as Template" / library blok
 * Elementor Pro. BEDA dari App\Models\ReusableBlock (tabel `blocks`, isinya
 * disinkronkan ke semua pemakai): template ini disalin SEKALI saat dipakai
 * (App\Http\Controllers\Admin\SectionTemplateController::insert()), lalu
 * hidup sendiri di halaman itu — mengedit halaman tidak mengubah template,
 * dan mengedit template tidak mengubah halaman yang sudah memakainya.
 */
class SectionTemplate extends Model
{
    use HasSlug;
    use LogsActivity;

    protected $guarded = [];

    protected $casts = [
        'section' => 'array',
    ];

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
}
