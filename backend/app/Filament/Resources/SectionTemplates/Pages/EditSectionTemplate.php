<?php

namespace App\Filament\Resources\SectionTemplates\Pages;

use App\Filament\Resources\SectionTemplates\SectionTemplateResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSectionTemplate extends EditRecord
{
    protected static string $resource = SectionTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
