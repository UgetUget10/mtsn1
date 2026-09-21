<?php

namespace App\Filament\Resources\Teachers\Pages;

use App\Filament\Resources\Teachers\Actions\ImportTeachersAction;
use App\Filament\Resources\Teachers\TeacherResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTeachers extends ListRecords
{
    protected static string $resource = TeacherResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ImportTeachersAction::make(),
            CreateAction::make(),
        ];
    }
}
