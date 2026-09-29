<?php

namespace App\Filament\Resources\GlobalTemplates\Tables;

use App\Models\GlobalTemplate;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class GlobalTemplatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama desain')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('slot')
                    ->label('Posisi')
                    ->formatStateUsing(fn (string $state) => GlobalTemplate::slotOptions()[$state] ?? $state)
                    ->badge()
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Tayang')
                    ->boolean(),

                TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('slot')->options(GlobalTemplate::slotOptions()),
            ])
            ->defaultSort('slot')
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
