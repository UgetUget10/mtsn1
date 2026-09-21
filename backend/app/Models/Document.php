<?php

namespace App\Models;

use App\Models\Concerns\TriggersFrontendRevalidation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Document extends Model implements HasMedia
{
    use InteractsWithMedia;
    use LogsActivity;
    use SoftDeletes;
    use TriggersFrontendRevalidation;

    protected $guarded = [];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected static function booted(): void
    {
        // Kolom `file` string legacy — dipertahankan untuk data lama yang belum
        // dimigrasi ke media library (lihat App\Console\Commands\MigrateLegacyMedia).
        static::saving(function (self $doc) {
            if ($doc->isDirty('file') && $doc->file && Storage::disk('public')->exists($doc->file)) {
                $doc->mime = Storage::disk('public')->mimeType($doc->file) ?: null;
                $doc->size = Storage::disk('public')->size($doc->file);
            }
        });

        // SpatieMediaLibraryFileUpload menyimpan berkas ke media library langsung
        // (bukan ke kolom `file`), jadi mime/size diisi dari entri media sesudah
        // form tersimpan — kolom ini yang dibaca DocumentsTable & API publik.
        static::saved(function (self $doc) {
            $media = $doc->getFirstMedia('file');
            if (! $media) {
                return;
            }

            if ($doc->mime === $media->mime_type && $doc->size === $media->size) {
                return;
            }

            $doc->newQuery()->whereKey($doc->getKey())->update([
                'mime' => $media->mime_type,
                'size' => $media->size,
            ]);
        });
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('file')->singleFile();
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
