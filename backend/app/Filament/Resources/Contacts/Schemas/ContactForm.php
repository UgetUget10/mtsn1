<?php

namespace App\Filament\Resources\Contacts\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ContactForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->label('Nama')->disabled(),
                TextInput::make('email')->label('Email')->disabled(),
                TextInput::make('phone')->label('Telepon')->disabled(),
                TextInput::make('subject')->label('Subjek')->disabled(),
                Textarea::make('message')
                    ->label('Pesan')
                    ->rows(8)
                    ->disabled()
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }
}
