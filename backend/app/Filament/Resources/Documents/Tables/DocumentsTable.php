<?php

namespace App\Filament\Resources\Documents\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use App\Filament\Support\ForceDeleteImpact;
use App\Models\Document;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Columns\Column as ExportColumn;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class DocumentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Judul')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('category.name')
                    ->label('Kategori')
                    ->badge()
                    ->color('gray')
                    ->placeholder('—'),
                TextColumn::make('size')
                    ->label('Ukuran')
                    ->formatStateUsing(fn (?int $state) => $state ? self::humanSize($state) : '—')
                    ->sortable(),
                TextColumn::make('downloads')
                    ->label('Unduhan')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Ditambahkan')
                    ->dateTime('d M Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('category_id')
                    ->label('Kategori')
                    ->relationship('category', 'name'),
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
                            'berkas' => fn (Document $r) => $r->getMedia('file')->count(),
                        ])),
                    ExportBulkAction::make()
                        ->label('Ekspor ke Excel')
                        ->exports([
                            ExcelExport::make()
                                ->withFilename('dokumen-'.now()->format('Y-m-d'))
                                ->withColumns([
                                    ExportColumn::make('title')->heading('Judul'),
                                    ExportColumn::make('category.name')->heading('Kategori'),
                                    ExportColumn::make('downloads')->heading('Unduhan'),
                                    ExportColumn::make('created_at')->heading('Ditambahkan'),
                                ]),
                        ]),
                ]),
            ]);
    }

    private static function humanSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        $n = (float) $bytes;
        while ($n >= 1024 && $i < count($units) - 1) {
            $n /= 1024;
            $i++;
        }

        return round($n, 1).' '.$units[$i];
    }
}
