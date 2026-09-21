<?php

namespace App\Filament\Resources\Tags\Pages;

use App\Filament\Concerns\SyncsTranslatableFields;
use App\Filament\Resources\Tags\TagResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTag extends CreateRecord
{
    use SyncsTranslatableFields;

    protected static string $resource = TagResource::class;

    protected function translatableAttributes(): array
    {
        return ['name'];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->collapseTranslatableFields($data);
    }
}
