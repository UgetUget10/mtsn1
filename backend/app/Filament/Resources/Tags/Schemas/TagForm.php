<?php

namespace App\Filament\Resources\Tags\Schemas;

use App\Filament\Concerns\HasTranslatableTabs;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class TagForm
{
    use HasTranslatableTabs;

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                static::translatableTabs('tag_tabs', fn (string $locale) => [
                    TextInput::make("name.{$locale}")
                        ->label('Nama')
                        ->required($locale === array_key_first(config('translatable.locales', ['id' => null])))
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (?string $state, callable $set) use ($locale) {
                            if ($locale === array_key_first(config('translatable.locales', ['id' => null]))) {
                                $set('slug', Str::slug((string) $state));
                            }
                        }),
                ])->columnSpanFull(),
                TextInput::make('slug')
                    ->required()
                    ->unique(ignoreRecord: true),
                Textarea::make('description')
                    ->label('Deskripsi')
                    ->rows(2)
                    ->maxLength(500)
                    ->helperText('Tampil di header halaman arsip tag (wp: tag description).')
                    ->columnSpanFull(),
            ]);
    }
}
