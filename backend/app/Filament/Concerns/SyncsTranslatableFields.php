<?php

namespace App\Filament\Concerns;

/**
 * Pasangan App\Filament\Concerns\HasTranslatableTabs. Dipakai di halaman
 * Create/Edit Filament untuk model ber-HasTranslations: mengisi form dengan
 * field bernama "{atribut}.{locale}" saat fill, dan menggabungkannya kembali
 * jadi array translasi utuh saat disimpan (supaya locale yang tidak sedang
 * diedit tidak tertimpa/hilang).
 *
 * Catatan penting: field Filament bernama "title.id" menghasilkan STATE
 * BERSARANG `['title' => ['id' => ...]]`, bukan key datar `"title.id"` —
 * jadi baca/tulisnya lewat array bersarang, bukan string key.
 */
trait SyncsTranslatableFields
{
    /**
     * @return array<int, string>
     */
    protected function translatableAttributes(): array
    {
        return [];
    }

    /**
     * Panggil dari mutateFormDataBeforeFill() di halaman Edit.
     */
    protected function expandTranslatableFields(array $data, $record): array
    {
        foreach ($this->translatableAttributes() as $attr) {
            $perLocale = [];
            foreach (array_keys(config('translatable.locales', [])) as $locale) {
                $perLocale[$locale] = $record->getTranslation($attr, $locale, false);
            }
            $data[$attr] = $perLocale;
        }

        return $data;
    }

    /**
     * Panggil dari mutateFormDataBeforeCreate()/mutateFormDataBeforeSave().
     */
    protected function collapseTranslatableFields(array $data, $existingRecord = null): array
    {
        foreach ($this->translatableAttributes() as $attr) {
            $translations = $existingRecord?->getTranslations($attr) ?? [];
            $submitted = $data[$attr] ?? [];

            foreach (array_keys(config('translatable.locales', [])) as $locale) {
                if (! array_key_exists($locale, $submitted)) {
                    continue;
                }

                $value = $submitted[$locale];
                if (filled($value)) {
                    $translations[$locale] = $value;
                } else {
                    unset($translations[$locale]);
                }
            }

            $data[$attr] = $translations;
        }

        return $data;
    }
}
