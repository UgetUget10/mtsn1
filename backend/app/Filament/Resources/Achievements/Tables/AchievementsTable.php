<?php

namespace App\Filament\Resources\Achievements\Tables;

use App\Filament\Resources\Achievements\AchievementResource;
use App\Filament\Support\ForceDeleteImpact;
use App\Models\Achievement;
use App\Support\Content\Duplicator;
use Filament\Actions\Action;
use Filament\Actions\Action as NotificationAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Columns\Column as ExportColumn;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class AchievementsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('image')->collection('image')->label('')->square(),
                TextColumn::make('title')
                    ->label('Prestasi')
                    ->searchable()
                    ->wrap()
                    ->description(fn ($record) => $record->student_name),
                TextColumn::make('level')
                    ->label('Tingkat')
                    ->badge()
                    ->color(fn (?string $state) => match ($state) {
                        'internasional' => 'success',
                        'nasional' => 'warning',
                        'provinsi' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state) => $state ? ucfirst($state) : '—'),
                TextColumn::make('year')->label('Tahun')->sortable(),
                TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('year', 'desc')
            ->filters([
                SelectFilter::make('level')
                    ->label('Tingkat')
                    ->options([
                        'kecamatan' => 'Kecamatan',
                        'kota' => 'Kota/Kabupaten',
                        'provinsi' => 'Provinsi',
                        'nasional' => 'Nasional',
                        'internasional' => 'Internasional',
                    ]),
                TrashedFilter::make(), // wp: tampilan "Trash"
            ])
            ->recordActions([
                Action::make('duplicate')
                    ->label('Duplikat')
                    ->icon('heroicon-m-document-duplicate')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalHeading('Duplikat prestasi ini?')
                    ->modalDescription('Berguna untuk mencatat prestasi serupa (mis. lomba tahun berikutnya) dengan cepat.')
                    ->modalSubmitActionLabel('Duplikat')
                    ->action(function (Achievement $record) {
                        $copy = Duplicator::achievement($record);

                        Notification::make()
                            ->success()
                            ->title('Prestasi diduplikat')
                            ->actions([
                                NotificationAction::make('edit')
                                    ->label('Sunting salinan')
                                    ->url(AchievementResource::getUrl('edit', ['record' => $copy]))
                                    ->button(),
                            ])
                            ->send();
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make()
                        ->modalDescription(fn (Collection $records) => ForceDeleteImpact::describe($records, [
                            'gambar' => fn (Achievement $r) => $r->getMedia('image')->count(),
                        ])),
                    ExportBulkAction::make()
                        ->label('Ekspor ke Excel')
                        ->exports([
                            ExcelExport::make()
                                ->withFilename('prestasi-'.now()->format('Y-m-d'))
                                ->withColumns([
                                    ExportColumn::make('title')->heading('Prestasi'),
                                    ExportColumn::make('student_name')->heading('Nama Siswa'),
                                    ExportColumn::make('level')->heading('Tingkat'),
                                    ExportColumn::make('year')->heading('Tahun'),
                                ]),
                        ]),
                ]),
            ]);
    }
}
