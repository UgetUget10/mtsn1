<?php

namespace App\Filament\Resources\Extracurriculars\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use App\Filament\Support\ForceDeleteImpact;
use App\Models\Extracurricular;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class ExtracurricularsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('image')->collection('image')->label('')->square(),
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('coach')
                    ->label('Pembina')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('schedule')
                    ->label('Jadwal')
                    ->placeholder('—'),
            ])
            ->defaultSort('name')
            ->filters([
                TrashedFilter::make(), // wp: tampilan "Trash"
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make()
                        ->modalDescription(fn (Collection $records) => ForceDeleteImpact::describe($records, [
                            'gambar' => fn (Extracurricular $r) => $r->getMedia('image')->count(),
                        ])),
                ]),
            ]);
    }
}
