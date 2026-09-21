<?php

namespace App\Filament\Resources\Categories\Tables;

use App\Filament\Resources\Categories\Actions\MergeCategoriesAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->reorderable('order')
            ->defaultSort('order')
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable()
                    // Prefiks "— " per tingkat kedalaman (wp: daftar kategori berjenjang).
                    ->formatStateUsing(function ($state, $record) {
                        $depth = 0;
                        for ($p = $record->parent; $p; $p = $p->parent) {
                            $depth++;
                        }

                        return ($depth ? str_repeat('— ', $depth) : '').$state;
                    }),
                TextColumn::make('parent.name')
                    ->label('Induk')
                    ->placeholder('—')
                    ->color('gray')
                    ->toggleable(),
                TextColumn::make('slug')
                    ->label('Slug')
                    ->color('gray')
                    ->searchable(),
                TextColumn::make('type')
                    ->label('Jenis')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'post' => 'Berita',
                        'gallery' => 'Galeri',
                        'document' => 'Dokumen',
                        default => $state,
                    }),
                TextColumn::make('posts_count')
                    ->label('Berita')
                    ->counts('posts')
                    ->badge()
                    ->color('gray'),
            ])
            ->defaultSort('name')
            ->filters([
                SelectFilter::make('type')
                    ->label('Jenis')
                    ->options([
                        'post' => 'Berita',
                        'gallery' => 'Galeri',
                        'document' => 'Dokumen',
                    ]),
                SelectFilter::make('parent_id')
                    ->label('Kategori induk')
                    ->relationship('parent', 'name'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    MergeCategoriesAction::make(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
