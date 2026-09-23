<?php

namespace App\Support\Blocks;

/**
 * Kosakata tertutup untuk `style` per node tree kanvas visual (Phase 2:
 * panel styling) — mencegah nilai bebas/CSS arbitrer tersimpan ke DB.
 * Dipakai App\Http\Controllers\Admin\PageCanvasController::update() untuk
 * memvalidasi & membuang key/value yang tak dikenal sebelum menyimpan, dan
 * cerminannya di frontend/src/lib/style-engine.ts untuk mengompilasi nilai
 * yang sudah tervalidasi ini jadi CSS custom properties saat render.
 *
 * Bentuk `style` per node: `{ base: {...}, md: {...}, lg: {...} }` — key
 * breakpoint mengikuti default Tailwind v4 yang sudah dipakai di globals.css.
 */
class StyleResolver
{
    public const BREAKPOINTS = ['base', 'sm', 'md', 'lg', 'xl'];

    /** Skala padding vertikal — nilai rem selaras dengan --section-y di globals.css. */
    public const PADDING_Y_SCALE = ['none', 'sm', 'md', 'lg', 'xl'];

    /**
     * Token warna latar yang tersedia sebagai preset cepat di panel — dari
     * frontend/src/app/globals.css. Editor tetap bisa pilih warna bebas
     * (hex), preset ini hanya kemudahan (lihat StyleResolver::isValidColor()).
     */
    public const BACKGROUND_PRESETS = [
        'transparent', 'surface', 'surface-muted', 'brand-light', 'accent-soft', 'success-soft', 'info-soft',
    ];

    public const TEXT_ALIGN_VALUES = ['left', 'center', 'right'];

    public const COLUMN_WIDTHS = [3, 4, 6, 8, 9, 12];

    /**
     * Bersihkan `style` satu node: buang breakpoint/key/value yang tak
     * dikenal, kembalikan struktur yang aman disimpan. Node kosong/tanpa
     * style yang valid menghasilkan array kosong (bukan null) — konsisten
     * dengan bentuk `style: {}` yang sudah dipakai TreeNormalizer.
     *
     * @param  mixed  $style
     * @return array<string, array<string, mixed>>
     */
    public static function sanitize($style): array
    {
        if (! is_array($style)) {
            return [];
        }

        $clean = [];

        foreach (self::BREAKPOINTS as $bp) {
            if (! isset($style[$bp]) || ! is_array($style[$bp])) {
                continue;
            }

            $props = self::sanitizeProperties($style[$bp]);
            if (! empty($props)) {
                $clean[$bp] = $props;
            }
        }

        return $clean;
    }

    /**
     * @param  array<string, mixed>  $props
     * @return array<string, mixed>
     */
    private static function sanitizeProperties(array $props): array
    {
        $clean = [];

        if (isset($props['width']) && in_array($props['width'], self::COLUMN_WIDTHS, true)) {
            $clean['width'] = $props['width'];
        }

        if (isset($props['paddingY']) && in_array($props['paddingY'], self::PADDING_Y_SCALE, true)) {
            $clean['paddingY'] = $props['paddingY'];
        }

        if (isset($props['background']) && self::isValidColor($props['background'])) {
            $clean['background'] = $props['background'];
        }

        if (isset($props['textAlign']) && in_array($props['textAlign'], self::TEXT_ALIGN_VALUES, true)) {
            $clean['textAlign'] = $props['textAlign'];
        }

        return $clean;
    }

    /**
     * Nilai warna valid: salah satu token preset, atau hex 3/6 digit
     * (#abc / #aabbcc) — cukup ketat untuk mencegah injeksi CSS lewat value
     * (mis. `background: url(...)` atau `; } body { ...`) tanpa melarang
     * warna bebas yang jadi keputusan produk di fitur ini.
     */
    public static function isValidColor(mixed $value): bool
    {
        if (! is_string($value)) {
            return false;
        }

        if (in_array($value, self::BACKGROUND_PRESETS, true)) {
            return true;
        }

        return (bool) preg_match('/^#[0-9a-fA-F]{3}([0-9a-fA-F]{3})?$/', $value);
    }
}
