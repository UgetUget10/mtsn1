<?php

namespace App\Filament\Resources\Contacts\Tables;

use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class ContactsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                IconColumn::make('is_read')
                    ->label('')
                    ->boolean()
                    ->trueIcon('heroicon-o-envelope-open')
                    ->falseIcon('heroicon-s-envelope')
                    ->falseColor('warning'),
                TextColumn::make('name')->label('Nama')->searchable()->weight('bold'),
                TextColumn::make('subject')->label('Subjek')->searchable()->limit(40),
                TextColumn::make('email')->label('Email')->searchable()->toggleable(),
                TextColumn::make('phone')->label('Telepon')->toggleable(),
                TextColumn::make('created_at')->label('Diterima')->dateTime('d M Y H:i')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                TernaryFilter::make('is_read')
                    ->label('Status baca')
                    ->trueLabel('Sudah dibaca')
                    ->falseLabel('Belum dibaca'),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('markRead')
                        ->label('Tandai sudah dibaca')
                        ->icon('heroicon-o-envelope-open')
                        ->action(fn (Collection $records) => $records->each->update(['is_read' => true]))
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
