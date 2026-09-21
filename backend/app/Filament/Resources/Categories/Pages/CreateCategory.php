<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Concerns\SyncsTranslatableFields;
use App\Filament\Resources\Categories\CategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCategory extends CreateRecord
{
    use SyncsTranslatableFields;

    protected static string $resource = CategoryResource::class;

    protected function translatableAttributes(): array
    {
        return ['name'];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->collapseTranslatableFields($data);
    }
}
