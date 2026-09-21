<?php

namespace App\Filament\Resources\Menus\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class MenuForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('key')
                    ->label('Kunci')
                    ->helperText('Pengenal unik, mis. "header" atau "footer" — dipakai frontend untuk memanggil menu ini.')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->disabledOn('edit'),
                TextInput::make('label')
                    ->label('Nama menu')
                    ->required(),
            ])
            ->columns(2);
    }
}
