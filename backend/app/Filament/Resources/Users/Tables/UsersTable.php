<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use STS\FilamentImpersonate\Actions\Impersonate;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('avatar')
                    ->label('')
                    ->circular()
                    ->getStateUsing(fn ($record) => $record->avatarUrl()),
                TextColumn::make('name')
                    ->label('Nama')
                    ->description(fn ($record) => $record->job_title)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('roles.name')
                    ->label('Peran')
                    ->badge()
                    ->separator(','),
                TextColumn::make('posts_count')
                    ->label('Berita')
                    ->counts('posts')
                    ->badge()
                    ->color('gray'),
                IconColumn::make('show_publicly')
                    ->label('Publik')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('Terdaftar')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('name')
            ->filters([
                SelectFilter::make('roles')
                    ->label('Peran')
                    ->relationship('roles', 'name'),
                TernaryFilter::make('show_publicly')
                    ->label('Profil publik'),
            ])
            ->recordActions([
                Impersonate::make()
                    ->label('Login sebagai')
                    ->redirectTo(fn () => route('filament.admin.pages.dashboard')),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
