<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Akun')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama')
                            ->required(),
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true),
                        TextInput::make('slug')
                            ->label('Slug publik')
                            ->helperText('Dipakai di URL arsip penulis: /penulis/{slug}. Kosongkan untuk otomatis dari nama.')
                            ->unique(ignoreRecord: true),
                        TextInput::make('password')
                            ->label('Kata sandi')
                            ->password()
                            ->revealable()
                            ->required(fn (string $operation) => $operation === 'create')
                            ->dehydrated(fn (?string $state) => filled($state))
                            ->dehydrateStateUsing(fn (string $state) => bcrypt($state))
                            ->helperText('Kosongkan bila tidak ingin mengganti kata sandi.'),
                        Select::make('roles')
                            ->label('Peran')
                            ->relationship('roles', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->required(),
                    ]),

                Section::make('Profil publik')
                    ->description('Tampil di halaman arsip penulis (/penulis/{slug}) dan sebagai byline artikel. Setara "Biographical Info" di WordPress.')
                    ->columns(2)
                    ->schema([
                        Toggle::make('show_publicly')
                            ->label('Tampilkan profil ini sebagai penulis publik')
                            ->default(true)
                            ->columnSpanFull(),
                        TextInput::make('job_title')
                            ->label('Jabatan / peran')
                            ->placeholder('mis. Guru Bahasa Indonesia'),
                        SpatieMediaLibraryFileUpload::make('avatar')
                            ->collection('avatar')
                            ->label('Foto profil')
                            ->image()
                            ->avatar()
                            ->directory('avatars')
                            ->imageEditor(),
                        Textarea::make('bio')
                            ->label('Biografi singkat')
                            ->rows(4)
                            ->maxLength(600)
                            ->columnSpanFull(),
                        TextInput::make('social.website')->label('Situs web')->url()->prefixIcon('heroicon-o-globe-alt'),
                        TextInput::make('social.instagram')->label('Instagram')->url(),
                        TextInput::make('social.twitter')->label('X / Twitter')->url(),
                        TextInput::make('social.linkedin')->label('LinkedIn')->url(),
                        TextInput::make('social.scholar')->label('Google Scholar')->url(),
                    ]),
            ]);
    }
}
