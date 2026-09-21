<?php

namespace App\Filament\Resources\MediaLibrary\Pages;

use App\Filament\Resources\MediaLibrary\MediaResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class EditMedia extends EditRecord
{
    protected static string $resource = MediaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('open')
                ->label('Buka berkas')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('gray')
                ->url(fn () => $this->getRecord()->getUrl(), shouldOpenInNewTab: true),
            DeleteAction::make(),
        ];
    }

    /**
     * `custom_properties` disimpan sebagai kolom JSON — Filament mengisinya
     * dari titik-notasi form ("custom_properties.alt"). Bersihkan nilai
     * kosong agar tidak menyimpan `{"alt": ""}` yang membuat badge "belum
     * diisi" salah hitung.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $props = array_filter(
            $data['custom_properties'] ?? [],
            fn ($v) => $v !== null && $v !== '',
        );
        $data['custom_properties'] = $props;

        return $data;
    }

    protected function afterSave(): void
    {
        // Bersihkan cache konversi supaya frontend menerima alt terbaru
        // pada respons berikutnya (media tidak punya TriggersFrontendRevalidation,
        // jadi cukup andalkan revalidate berkala frontend).
        /** @var Media $media */
        $media = $this->getRecord();
        $media->refresh();
    }
}
