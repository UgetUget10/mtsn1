<?php

namespace App\Filament\Resources\SectionTemplates\Pages;

use App\Filament\Resources\SectionTemplates\SectionTemplateResource;
use Filament\Resources\Pages\ListRecords;

/** Tanpa CreateAction — template hanya dibuat lewat kanvas visual, lihat SectionTemplateResource. */
class ListSectionTemplates extends ListRecords
{
    protected static string $resource = SectionTemplateResource::class;
}
