<?php

namespace App\Models;

use App\Models\Concerns\TriggersFrontendRevalidation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Satu zona widget (sidebar, footer, dst.) — lihat App\Support\Widgets\
 * WidgetAreaKeys untuk daftar zona yang benar-benar dirender frontend.
 * Isinya adalah kumpulan ReusableBlock (tabel `blocks`) terurut lewat pivot
 * `widget_area_items` — setara "drag widget ke sidebar" WordPress, tapi
 * "widget"-nya adalah blok konten yang sudah ada di sistem (Reusable Block),
 * bukan tipe widget terpisah.
 */
class WidgetArea extends Model
{
    use TriggersFrontendRevalidation;

    protected $guarded = [];

    public function blocks(): BelongsToMany
    {
        return $this->belongsToMany(ReusableBlock::class, 'widget_area_items', 'widget_area_id', 'block_id')
            ->withPivot(['order', 'is_active'])
            ->using(WidgetAreaItem::class)
            ->orderByPivot('order');
    }

    /** Blok aktif saja, siap dirender apa adanya — dipakai API publik. */
    public function activeBlocks(): BelongsToMany
    {
        return $this->blocks()
            ->wherePivot('is_active', true)
            ->where('blocks.is_active', true);
    }
}
