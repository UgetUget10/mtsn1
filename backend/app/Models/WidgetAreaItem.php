<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

/** Pivot bernama (bukan anonim) supaya bisa dipakai relation manager langsung. */
class WidgetAreaItem extends Pivot
{
    protected $table = 'widget_area_items';

    protected $casts = ['is_active' => 'boolean'];

    public $incrementing = true;
}
