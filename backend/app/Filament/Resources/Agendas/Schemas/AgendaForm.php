<?php

namespace App\Filament\Resources\Agendas\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AgendaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('Judul')
                    ->required(),
                TextInput::make('slug')
                    ->label('Slug')
                    ->helperText('Kosongkan untuk otomatis dari judul.')
                    ->unique(ignoreRecord: true),
                Textarea::make('description')
                    ->label('Deskripsi')
                    ->rows(4)
                    ->columnSpanFull(),
                DateTimePicker::make('start_at')
                    ->label('Waktu mulai')
                    ->seconds(false)
                    ->required(),
                DateTimePicker::make('end_at')
                    ->label('Waktu selesai')
                    ->seconds(false)
                    ->after('start_at'),
                TextInput::make('location')
                    ->label('Lokasi')
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }
}
