<?php

namespace App\Filament\Resources\ReusableBlocks\Tables;

use App\Models\ReusableBlock;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ReusableBlocksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('slug')
                    ->label('Slug')
                    ->badge()
                    ->color('gray')
                    ->copyable()
                    ->searchable(),

                TextColumn::make('blocks_count')
                    ->label('Jumlah blok')
                    ->state(fn (ReusableBlock $record) => count($record->content ?? []))
                    ->badge(),

                // Sengaja IconColumn (read-only), BUKAN ToggleColumn: ToggleColumn
                // memanggil save() yang membuat spatie/laravel-sluggable
                // meregenerasi slug — rujukan halaman bisa putus diam-diam.
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),

                TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('name')
            ->filters([
                TernaryFilter::make('is_active')->label('Status aktif'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
