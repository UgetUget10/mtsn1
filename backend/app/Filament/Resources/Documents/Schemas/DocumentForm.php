<?php

namespace App\Filament\Resources\Documents\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class DocumentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')->label('Judul')->required(),
                Select::make('category_id')
                    ->label('Kategori')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),
                SpatieMediaLibraryFileUpload::make('file')
                    ->collection('file')
                    ->label('Berkas')
                    ->required()
                    ->disk('public')
                    ->directory('documents')
                    ->downloadable()
                    ->openable()
                    ->maxSize(25 * 1024) // 25 MB
                    ->acceptedFileTypes([
                        'application/pdf',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'application/vnd.ms-powerpoint',
                        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                        'image/jpeg',
                        'image/png',
                    ])
                    ->helperText('PDF, Word, Excel, PowerPoint, atau gambar (JPG/PNG). Maksimal 25 MB.'),
            ]);
    }
}
