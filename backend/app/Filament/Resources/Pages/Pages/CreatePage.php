<?php

namespace App\Filament\Resources\Pages\Pages;

use App\Filament\Concerns\SyncsTranslatableFields;
use App\Filament\Resources\Pages\PageResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePage extends CreateRecord
{
    use SyncsTranslatableFields;

    protected static string $resource = PageResource::class;

    protected function translatableAttributes(): array
    {
        return ['title', 'meta_description'];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->collapseTranslatableFields($data);
    }
}
