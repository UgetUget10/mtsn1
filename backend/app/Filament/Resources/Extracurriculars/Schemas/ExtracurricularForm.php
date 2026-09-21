<?php

namespace App\Filament\Resources\Extracurriculars\Schemas;

use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ExtracurricularForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama')
                    ->required(),
                TextInput::make('slug')
                    ->label('Slug')
                    ->helperText('Kosongkan untuk otomatis dari nama.')
                    ->unique(ignoreRecord: true),
                TextInput::make('coach')
                    ->label('Pembina'),
                TextInput::make('schedule')
                    ->label('Jadwal')
                    ->placeholder('Setiap Jumat, 14.00 - 16.00'),
                SpatieMediaLibraryFileUpload::make('image')
                    ->collection('image')
                    ->label('Gambar')
                    ->image()
                    ->directory('extracurriculars')
                    ->imageEditor(),
                Textarea::make('description')
                    ->label('Deskripsi')
                    ->rows(4)
                    ->columnSpanFull(),
            ]);
    }
}
