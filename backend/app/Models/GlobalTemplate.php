<?php

namespace App\Models;

use App\Support\Blocks\TreeNormalizer;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Template global kanvas visual (header/footer) — "Theme Builder" ala
 * Elementor Pro. Beda dari Page: tidak punya slug/URL publik sendiri, hanya
 * `slot` ('header'/'footer') yang menentukan di mana ia tampil. Beberapa
 * baris boleh berbagi `slot` yang sama (draft desain alternatif), tapi hanya
 * SATU yang `is_active=true` per slot — itulah yang dirender situs publik
 * (lihat scopeActiveForSlot() dan App\Http\Controllers\Api\GlobalTemplateController).
 */
class GlobalTemplate extends Model
{
    use LogsActivity;

    public const SLOT_HEADER = 'header';

    public const SLOT_FOOTER = 'footer';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'tree' => 'array',
        'tree_draft' => 'array',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'slot', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /** @return array<string, string> */
    public static function slotOptions(): array
    {
        return [
            self::SLOT_HEADER => 'Header',
            self::SLOT_FOOTER => 'Footer',
        ];
    }

    /**
     * Struktur tree kanvas visual untuk disunting — draf jika ada, jika
     * tidak dinormalisasi dari `tree` yang published. Sama seperti
     * Page::visibleTree(), tidak pernah menulis ke DB di sini.
     *
     * @return array{schema: int, tree: array<int, array<string, mixed>>}
     */
    public function visibleTree(): array
    {
        if (! empty($this->tree_draft)) {
            return TreeNormalizer::normalize($this->tree_draft);
        }

        return TreeNormalizer::normalize($this->tree);
    }

    /**
     * Menandai baris ini aktif untuk slotnya, menonaktifkan baris lain di
     * slot yang sama — dipanggil saat publish, BUKAN otomatis saat dibuat,
     * supaya boleh ada beberapa desain tersimpan tanpa langsung tayang.
     */
    public function activate(): void
    {
        static::where('slot', $this->slot)->where('id', '!=', $this->id)->update(['is_active' => false]);
        $this->update(['is_active' => true]);
    }
}
