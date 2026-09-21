<?php

namespace App\Filament\Resources\Redirects\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class RedirectsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('from_path')->label('Dari')->searchable()->copyable(),
                TextColumn::make('to_path')
                    ->label('Ke')
                    ->searchable()
                    ->copyable()
                    // Tandai redirect yang tujuannya sudah tidak ada: 301 → 404
                    // ("redirect ke nowhere") merugikan SEO dan biasanya sisa
                    // dari uji coba slug. Lihat Redirect::targetExists().
                    ->badge()
                    ->color(fn ($record) => $record->targetExists() ? 'gray' : 'danger')
                    ->tooltip(fn ($record) => $record->targetExists()
                        ? null
                        : 'Tujuan tidak ditemukan — pengunjung akan mendapat 404. Perbaiki atau hapus baris ini.'),
                TextColumn::make('status')->label('Kode')->badge()->sortable(),
                TextColumn::make('source')->label('Sumber')->badge()->colors([
                    'gray' => 'slug-change',
                    'info' => 'manual',
                ]),
                TextColumn::make('hits')->label('Diakses')->numeric()->sortable(),
                TextColumn::make('last_hit_at')->label('Terakhir')->dateTime('d M Y H:i')->sortable()->placeholder('—'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('source')->options([
                    'slug-change' => 'Otomatis',
                    'manual' => 'Manual',
                ]),
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
