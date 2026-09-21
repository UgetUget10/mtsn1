<?php

namespace App\Filament\Resources\Galleries\Tables;

use App\Filament\Resources\Galleries\GalleryResource;
use App\Filament\Support\ForceDeleteImpact;
use App\Models\Gallery;
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
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class GalleriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('cover')->collection('cover')->label('')->square(),
                TextColumn::make('title')
                    ->label('Judul')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('category.name')
                    ->label('Kategori')
                    ->badge()
                    ->color('gray')
                    ->placeholder('—'),
                TextColumn::make('items_count')
                    ->label('Item')
                    ->counts('items')
                    ->badge(),
                TextColumn::make('taken_on')
                    ->label('Tanggal kegiatan')
                    ->date('d M Y')
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->defaultSort('taken_on', 'desc')
            ->filters([
                TrashedFilter::make(), // wp: tampilan "Trash"
            ])
            ->recordActions([
                Action::make('duplicate')
                    ->label('Duplikat')
                    ->icon('heroicon-m-document-duplicate')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalHeading('Duplikat galeri ini?')
                    ->modalDescription('Salinan dibuat dengan judul bertanda "(salinan)", termasuk seluruh foto/video di dalamnya.')
                    ->modalSubmitActionLabel('Duplikat')
                    ->action(function (Gallery $record) {
                        $copy = Duplicator::gallery($record);

                        Notification::make()
                            ->success()
                            ->title('Galeri diduplikat')
                            ->actions([
                                NotificationAction::make('edit')
                                    ->label('Sunting salinan')
                                    ->url(GalleryResource::getUrl('edit', ['record' => $copy]))
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
                            'item galeri' => fn (Gallery $r) => $r->items()->count(),
                        ])),
                ]),
            ]);
    }
}
