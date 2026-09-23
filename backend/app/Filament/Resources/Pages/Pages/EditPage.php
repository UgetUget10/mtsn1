<?php

namespace App\Filament\Resources\Pages\Pages;

use App\Filament\Concerns\HasAutosave;
use App\Filament\Concerns\SyncsTranslatableFields;
use App\Filament\Resources\Pages\PageResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditPage extends EditRecord
{
    use HasAutosave;
    use SyncsTranslatableFields;

    protected function autosaveFields(): array
    {
        return ['blocks'];
    }

    protected static string $resource = PageResource::class;

    protected function translatableAttributes(): array
    {
        return ['title', 'meta_description'];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('canvas')
                ->label('Edit Visual')
                ->icon('heroicon-o-squares-2x2')
                ->color('primary')
                ->url(fn () => static::getResource()::getUrl('canvas', ['record' => $this->getRecord()])),
            Action::make('preview')
                ->label('Pratinjau')
                ->icon('heroicon-o-eye')
                ->color('gray')
                ->url(fn () => $this->previewUrl(), shouldOpenInNewTab: true)
                ->visible(fn () => filled($this->previewUrl())),
            DeleteAction::make(),
            RestoreAction::make(),
            ForceDeleteAction::make(),
        ];
    }

    private function previewUrl(): ?string
    {
        $base = rtrim((string) env('FRONTEND_URL', ''), '/');
        if (! $base) {
            return null;
        }

        $record = $this->getRecord();

        return "{$base}/api/preview?type=page&slug={$record->slug}&token={$record->preview_token}";
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return $this->expandTranslatableFields($data, $this->getRecord());
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->collapseTranslatableFields($data, $this->getRecord());
    }
}
