<?php

namespace App\Filament\Resources\Tags\Tables;

use App\Filament\Resources\Tags\Actions\MergeTagsAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TagsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nama')->searchable()->sortable(),
                TextColumn::make('slug')->label('Slug')->color('gray')->searchable(),
                TextColumn::make('description')->label('Deskripsi')->limit(60)->placeholder('—')->toggleable(),
                TextColumn::make('posts_count')
                    ->label('Jumlah berita')
                    ->counts('posts')
                    ->sortable(),
            ])
            ->defaultSort('name')
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    MergeTagsAction::make(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
