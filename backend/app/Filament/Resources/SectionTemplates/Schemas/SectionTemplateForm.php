<?php

namespace App\Filament\Resources\SectionTemplates\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SectionTemplateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Nama template')
                ->required()
                ->maxLength(255),
        ]);
    }
}
