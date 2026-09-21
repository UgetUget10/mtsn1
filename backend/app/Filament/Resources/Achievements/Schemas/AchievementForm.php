<?php

namespace App\Filament\Resources\Achievements\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AchievementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('Judul prestasi')
                    ->required(),
                TextInput::make('student_name')
                    ->label('Nama siswa'),
                Select::make('level')
                    ->label('Tingkat')
                    ->options([
                        'kecamatan' => 'Kecamatan',
                        'kota' => 'Kota/Kabupaten',
                        'provinsi' => 'Provinsi',
                        'nasional' => 'Nasional',
                        'internasional' => 'Internasional',
                    ])
                    ->native(false),
                TextInput::make('year')
                    ->label('Tahun')
                    ->numeric()
                    ->minValue(1980)
                    ->maxValue((int) date('Y') + 1)
                    ->default((int) date('Y')),
                SpatieMediaLibraryFileUpload::make('image')
                    ->collection('image')
                    ->label('Gambar')
                    ->image()
                    ->imageEditor()
                    ->helperText('Klik ikon pensil pada gambar untuk memangkas sebelum menyimpan.')
                    ->directory('achievements'),
                Textarea::make('description')
                    ->label('Deskripsi')
                    ->columnSpanFull(),
            ]);
    }
}
