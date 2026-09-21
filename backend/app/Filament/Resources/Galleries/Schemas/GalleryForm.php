<?php

namespace App\Filament\Resources\Galleries\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class GalleryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('Judul')
                    ->required(),
                TextInput::make('slug')
                    ->helperText('Kosongkan untuk otomatis dari judul.')
                    ->unique(ignoreRecord: true),
                Select::make('category_id')
                    ->label('Kategori')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),
                DatePicker::make('taken_on')->label('Tanggal kegiatan'),
                Textarea::make('description')
                    ->label('Deskripsi')
                    ->columnSpanFull(),
                SpatieMediaLibraryFileUpload::make('cover')
                    ->collection('cover')
                    ->label('Sampul')
                    ->image()
                    ->directory('galleries')
                    ->imageEditor()
                    ->columnSpanFull(),
            ]);
    }
}
