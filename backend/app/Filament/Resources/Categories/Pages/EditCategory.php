<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Concerns\SyncsTranslatableFields;
use App\Filament\Resources\Categories\CategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCategory extends EditRecord
{
    use SyncsTranslatableFields;

    protected static string $resource = CategoryResource::class;

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
