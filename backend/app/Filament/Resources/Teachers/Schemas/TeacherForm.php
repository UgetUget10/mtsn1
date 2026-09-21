<?php

namespace App\Filament\Resources\Teachers\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class TeacherForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->label('Nama')->required(),
                TextInput::make('nip')->label('NIP'),
                TextInput::make('position')->label('Jabatan'),
                TextInput::make('subject')->label('Mata pelajaran'),
                TextInput::make('email')->email(),
                Select::make('group')
                    ->label('Kelompok')
                    ->options([
                        'pimpinan' => 'Pimpinan',
                        'guru' => 'Guru',
                        'tendik' => 'Tenaga Kependidikan',
                    ])
                    ->default('guru')
                    ->required(),
                TextInput::make('order')->label('Urutan')->numeric()->default(0),
                Toggle::make('is_active')->label('Aktif')->default(true),
                SpatieMediaLibraryFileUpload::make('photo')
                    ->collection('photo')
                    ->label('Foto')
                    ->image()
                    ->avatar()
                    // Frontend menampilkan foto dalam bingkai LINGKARAN dengan
                    // `object-top`. Editor dikunci 1:1 supaya hasil pangkasan
                    // sudah persegi sejak dari panel — komposisi wajah jadi
                    // urusan admin, bukan tebakan CSS.
                    ->imageEditor()
                    ->imageEditorAspectRatios(['1:1'])
                    ->imageEditorViewportWidth(500)
                    ->imageEditorViewportHeight(500)
                    ->helperText('Gunakan foto persegi. Klik ikon pensil pada foto untuk memangkas — pastikan wajah berada di bagian atas bingkai.')
                    ->directory('teachers')
                    ->columnSpanFull(),
            ]);
    }
}
