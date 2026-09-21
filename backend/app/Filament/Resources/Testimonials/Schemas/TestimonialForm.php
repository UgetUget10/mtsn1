<?php

namespace App\Filament\Resources\Testimonials\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class TestimonialForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Textarea::make('quote')
                    ->label('Kutipan testimoni')
                    ->required()
                    ->rows(4)
                    ->columnSpanFull(),
                TextInput::make('name')
                    ->label('Nama')
                    ->required(),
                TextInput::make('role')
                    ->label('Peran')
                    ->placeholder('Wali murid kelas VIII / Alumni angkatan 2022')
                    ->helperText('Bebas isi — jabatan, kelas anak, atau tahun lulus.'),
                TextInput::make('order')->label('Urutan')->numeric()->default(0),
                Toggle::make('is_active')->label('Aktif')->default(true),
            ]);
    }
}
