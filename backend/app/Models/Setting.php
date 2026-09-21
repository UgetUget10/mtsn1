<?php

namespace App\Models;

use App\Models\Concerns\TriggersFrontendRevalidation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Setting bukan model bergambar per baris (tiap baris cuma key/value biasa),
 * tapi tetap InteractsWithMedia agar baris logo/favicon/og_image/principal_photo
 * bisa punya media library sendiri — satu Media dilekatkan ke satu baris Setting
 * bertipe file lewat collection bernama sama dengan key-nya.
 */
class Setting extends Model implements HasMedia
{
    use InteractsWithMedia;
    use TriggersFrontendRevalidation;

    protected $guarded = [];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('file')->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')->width(400)->height(400)->nonQueued();
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('settings.all'));
        static::deleted(fn () => Cache::forget('settings.all'));
    }

    public static function values(): array
    {
        return Cache::rememberForever('settings.all', fn () => static::query()->pluck('value', 'key')->all());
    }

    public static function get(string $key, $default = null)
    {
        return static::values()[$key] ?? $default;
    }
}
