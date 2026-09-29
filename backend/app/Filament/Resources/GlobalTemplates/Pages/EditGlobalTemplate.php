<?php

namespace App\Filament\Resources\GlobalTemplates\Pages;

use App\Filament\Resources\GlobalTemplates\GlobalTemplateResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditGlobalTemplate extends EditRecord
{
    protected static string $resource = GlobalTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('canvas')
                ->label('Edit Visual')
                ->icon('heroicon-o-squares-2x2')
                ->color('primary')
                ->url(fn () => static::getResource()::getUrl('canvas', ['record' => $this->getRecord()])),
            DeleteAction::make(),
        ];
    }
}
