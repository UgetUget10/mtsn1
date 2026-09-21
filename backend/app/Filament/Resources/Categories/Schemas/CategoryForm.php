<?php

namespace App\Filament\Resources\Categories\Schemas;

use App\Filament\Concerns\HasTranslatableTabs;
use App\Models\Category;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CategoryForm
{
    use HasTranslatableTabs;

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                static::translatableTabs('category_translatable_tabs', fn (string $locale) => [
                    TextInput::make("name.{$locale}")
                        ->label('Nama')
                        ->required($locale === array_key_first(config('translatable.locales', ['id' => null]))),
                ])->columnSpanFull(),
                TextInput::make('slug')
                    ->helperText('Kosongkan untuk otomatis dari judul.')
                    ->unique(ignoreRecord: true),
                Select::make('type')
                    ->label('Jenis')
                    ->options([
                        'post' => 'Berita',
                        'gallery' => 'Galeri',
                        'document' => 'Dokumen',
                    ])
                    ->required()
                    ->default('post')
                    ->live(),
                Select::make('parent_id')
                    ->label('Kategori induk')
                    ->helperText('Kosongkan untuk kategori tingkat atas (wp: parent category).')
                    ->searchable()
                    ->preload()
                    ->options(function (Select $component, ?Category $record) {
                        $type = $component->getContainer()->getRawState()['type'] ?? 'post';

                        return Category::query()
                            ->where('type', $type)
                            ->when($record, fn ($q) => $q->whereKeyNot($record->getKey())
                                ->whereNotIn('id', $record->children()->pluck('id')))
                            ->orderBy('name')
                            ->pluck('name', 'id');
                    }),
                Textarea::make('description')
                    ->columnSpanFull(),
            ]);
    }
}
