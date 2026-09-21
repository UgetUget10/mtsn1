<?php

namespace App\Filament\Resources\Sliders\Schemas;

use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class SliderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                SpatieMediaLibraryFileUpload::make('image')
                    ->collection('image')
                    ->label('Gambar latar')
                    ->image()
                    ->imageEditor()
                    ->directory('sliders')
                    ->helperText('Opsional. Rasio ideal 16:9, minimal 1600px.')
                    ->columnSpanFull(),
                TextInput::make('title')->label('Judul'),
                TextInput::make('subtitle')->label('Subjudul'),
                TextInput::make('link')->label('Tautan tombol')->url(),
                TextInput::make('order')->label('Urutan')->numeric()->default(0),
                Toggle::make('is_active')->label('Aktif')->default(true),
            ]);
    }
}
