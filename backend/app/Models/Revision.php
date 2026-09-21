<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Satu snapshot atribut model pada satu titik waktu — setara wp_post_revisions.
 * Dibuat oleh trait App\Models\Concerns\HasRevisions saat model diperbarui.
 */
class Revision extends Model
{
    protected $guarded = [];

    protected $casts = [
        'data' => 'array',
    ];

    public function revisionable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
