<?php

namespace App\Filament\Resources\Tags\Pages;

use App\Filament\Concerns\SyncsTranslatableFields;
use App\Filament\Resources\Tags\TagResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTag extends EditRecord
{
    use SyncsTranslatableFields;

    protected static string $resource = TagResource::class;

    protected function translatableAttributes(): array
    {
        return ['name'];
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
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
