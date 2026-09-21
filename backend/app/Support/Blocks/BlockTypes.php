<?php

namespace App\Support\Blocks;

/**
 * Daftar tipe block yang didukung skema konten Page/Homepage. Satu sumber
 * kebenaran dipakai bersama oleh:
 * - App\Filament\Resources\Pages\Schemas\PageForm (definisi Builder block)
 * - App\Http\Resources\PageResource (transformasi ke API)
 * - frontend/src/components/blocks/BlockRenderer.tsx (render, lihat nama `type`)
 */
class BlockTypes
{
    public const HERO = 'hero';

    public const RICH_TEXT = 'rich_text';

    public const CARD_GRID = 'card_grid';

    public const ACCORDION = 'accordion';

    public const CTA = 'cta';

    public const FILE_LIST = 'file_list';

    public const GALLERY_BLOCK = 'gallery_block';

    public const STATS = 'stats';

    public const HUB_GRID = 'hub_grid';

    public const TABLE = 'table';

    public const STEPS = 'steps';

    public const QUOTE = 'quote';

    public const LINK_CARDS = 'link_cards';

    public const CHECKLIST = 'checklist';

    public const ICON_LIST = 'icon_list';

    public const TIMELINE = 'timeline';

    /** Referensi ke App\Models\ReusableBlock lewat data.slug (wp: Synced Pattern). */
    public const REUSABLE = 'reusable';

    public static function all(): array
    {
        return [
            self::HERO,
            self::RICH_TEXT,
            self::CARD_GRID,
            self::ACCORDION,
            self::CTA,
            self::FILE_LIST,
            self::GALLERY_BLOCK,
            self::STATS,
            self::HUB_GRID,
            self::TABLE,
            self::STEPS,
            self::QUOTE,
            self::LINK_CARDS,
            self::CHECKLIST,
            self::ICON_LIST,
            self::TIMELINE,
            self::REUSABLE,
        ];
    }
}
