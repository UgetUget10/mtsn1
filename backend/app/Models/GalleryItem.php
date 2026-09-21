<?php

namespace App\Models;

use App\Models\Concerns\TriggersFrontendRevalidation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class GalleryItem extends Model implements HasMedia
{
    use InteractsWithMedia;
    use TriggersFrontendRevalidation;

    protected $guarded = [];

    public function gallery(): BelongsTo
    {
        return $this->belongsTo(Gallery::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('card')->width(1200)->height(900)->nonQueued();
        $this->addMediaConversion('thumb')->width(400)->height(300)->nonQueued();
    }
}
