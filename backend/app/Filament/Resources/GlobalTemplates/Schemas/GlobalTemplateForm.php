<?php

namespace App\Filament\Resources\GlobalTemplates\Schemas;

use App\Models\GlobalTemplate;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class GlobalTemplateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Nama desain')
                ->required()
                ->maxLength(255),

            Select::make('slot')
                ->label('Posisi')
                ->options(GlobalTemplate::slotOptions())
                ->required()
                ->disabledOn('edit'),
        ]);
    }
}
