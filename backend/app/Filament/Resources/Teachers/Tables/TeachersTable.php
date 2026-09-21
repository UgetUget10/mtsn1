<?php

namespace App\Filament\Resources\Teachers\Tables;

use App\Filament\Resources\Teachers\TeacherResource;
use App\Models\Teacher;
use App\Support\Content\Duplicator;
use Filament\Actions\Action;
use Filament\Actions\Action as NotificationAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Columns\Column as ExportColumn;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class TeachersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->reorderable('order')
            ->defaultSort('order')
            ->columns([
                SpatieMediaLibraryImageColumn::make('photo')->collection('photo')->label('')->circular(),
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->nip ? 'NIP '.$record->nip : null),
                TextColumn::make('position')
                    ->label('Jabatan')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('subject')
                    ->label('Mapel')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('group')
                    ->label('Kelompok')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'pimpinan' => 'Pimpinan',
                        'guru' => 'Guru',
                        'tendik' => 'Tenaga Kependidikan',
                        default => $state,
                    })
                    ->color(fn (string $state) => $state === 'pimpinan' ? 'warning' : 'gray'),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('group')
                    ->label('Kelompok')
                    ->options([
                        'pimpinan' => 'Pimpinan',
                        'guru' => 'Guru',
                        'tendik' => 'Tenaga Kependidikan',
                    ]),
                TernaryFilter::make('is_active')->label('Status aktif'),
            ])
            ->recordActions([
                Action::make('duplicate')
                    ->label('Duplikat')
                    ->icon('heroicon-m-document-duplicate')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalHeading('Duplikat data guru/tendik ini?')
                    ->modalDescription('Berguna sebagai kerangka saat menambah staf baru dengan jabatan/mapel serupa. NIP TIDAK ikut disalin — wajib diisi manual. Salinan nonaktif sampai dilengkapi.')
                    ->modalSubmitActionLabel('Duplikat')
                    ->action(function (Teacher $record) {
                        $copy = Duplicator::teacher($record);

                        Notification::make()
                            ->success()
                            ->title('Data diduplikat')
                            ->actions([
                                NotificationAction::make('edit')
                                    ->label('Sunting salinan')
                                    ->url(TeacherResource::getUrl('edit', ['record' => $copy]))
                                    ->button(),
                            ])
                            ->send();
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ExportBulkAction::make()
                        ->label('Ekspor ke Excel')
                        ->exports([
                            ExcelExport::make()
                                ->withFilename('guru-tendik-'.now()->format('Y-m-d'))
                                ->withColumns([
                                    ExportColumn::make('name')->heading('Nama'),
                                    ExportColumn::make('nip')->heading('NIP'),
                                    ExportColumn::make('position')->heading('Jabatan'),
                                    ExportColumn::make('subject')->heading('Mapel'),
                                    ExportColumn::make('group')->heading('Kelompok'),
                                    ExportColumn::make('is_active')->heading('Aktif'),
                                ]),
                        ]),
                ]),
            ]);
    }
}
