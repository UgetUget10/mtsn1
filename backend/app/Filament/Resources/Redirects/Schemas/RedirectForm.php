<?php

namespace App\Filament\Resources\Redirects\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class RedirectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('from_path')
                    ->label('Dari path')
                    ->required()
                    ->prefix(config('app.url'))
                    ->helperText('Contoh: /berita/judul-lama')
                    ->unique(ignoreRecord: true),
                TextInput::make('to_path')
                    ->label('Ke path')
                    ->required()
                    ->helperText('Contoh: /berita/judul-baru'),
                Select::make('status')
                    ->label('Kode')
                    ->options([301 => '301 Permanen', 302 => '302 Sementara'])
                    ->default(301)
                    ->required(),
                Select::make('source')
                    ->label('Sumber')
                    ->options(['slug-change' => 'Perubahan slug (otomatis)', 'manual' => 'Manual'])
                    ->default('manual')
                    ->required(),
            ]);
    }
}
