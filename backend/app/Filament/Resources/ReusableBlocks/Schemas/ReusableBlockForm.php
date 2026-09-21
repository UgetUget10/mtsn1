<?php

namespace App\Filament\Resources\ReusableBlocks\Schemas;

use App\Filament\Resources\Pages\Schemas\PageForm;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ReusableBlockForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identitas')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama')
                            ->required()
                            ->maxLength(150)
                            // Slug diturunkan dari nama oleh spatie/laravel-sluggable
                            // dan slug itulah yang dirujuk halaman. Mengganti nama
                            // = mengganti slug = rujukan lama tak ketemu.
                            ->helperText('PERHATIAN: mengubah nama juga mengubah slug. Halaman yang sudah merujuk blok ini akan kehilangan isinya — perbarui halaman tersebut setelah mengganti nama.'),

                        TextInput::make('slug')
                            ->label('Slug')
                            ->disabled()
                            ->dehydrated(false)
                            ->visibleOn('edit')
                            ->helperText('Dibuat otomatis dari nama. Inilah yang dirujuk halaman.'),

                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true)
                            ->helperText('Bila dimatikan, isi blok tidak lagi tampil di halaman mana pun yang merujuknya.'),
                    ]),

                Section::make('Isi Blok')
                    ->description('Susunan blok yang akan disisipkan ke setiap halaman yang merujuk blok ini.')
                    ->schema([
                        // Definisi blok yang SAMA dengan PageForm, hanya beda nama
                        // state (kolomnya `content`, bukan `blocks`).
                        PageForm::builder('content')
                            ->default([]) // kolom `content` NOT NULL tanpa default
                            ->columnSpanFull(),
                    ]),
            ])
            ->columns(1);
    }
}
