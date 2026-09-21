<?php

namespace App\Filament\Concerns;

use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;

/**
 * Filament 5 belum punya field translatable native yang terikat ke
 * spatie/laravel-translatable (paket resmi `filament/spatie-laravel-translatable-plugin`
 * belum merilis versi yang kompatibel dengan Filament 5 — dicoba saat
 * implementasi, composer menolak semua versi yang tersedia). Helper ini jadi
 * jembatan manual: field translatable dibungkus tab per-locale, dengan nama
 * field `{atribut}.{locale}` (mis. `title.id`, `title.en`).
 *
 * Dipakai berpasangan dengan App\Filament\Concerns\SyncsTranslatableFields
 * di halaman Create/Edit resource untuk mengubah bentuk data saat isi form
 * (fill) dan simpan (save).
 */
trait HasTranslatableTabs
{
    /**
     * Bungkus schema field per-locale menjadi Tabs Filament, satu tab per
     * locale dari config('translatable.locales').
     *
     * @param  \Closure(string $locale): array<int, Component>  $schemaForLocale  Kembalikan array komponen field untuk satu locale, biasanya field bernama "{atribut}.{locale}".
     */
    public static function translatableTabs(string $tabsName, \Closure $schemaForLocale): Tabs
    {
        $locales = config('translatable.locales', ['id' => 'Indonesia']);

        $tabs = collect($locales)
            ->map(fn (string $label, string $locale) => Tab::make($locale)
                ->label($label)
                ->schema($schemaForLocale($locale)))
            ->values()
            ->all();

        return Tabs::make($tabsName)->tabs($tabs);
    }
}
