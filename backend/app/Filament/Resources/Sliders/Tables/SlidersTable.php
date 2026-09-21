<?php

namespace App\Filament\Resources\Sliders\Tables;

use App\Filament\Resources\Sliders\SliderResource;
use App\Models\Slider;
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
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class SlidersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->reorderable('order')
            ->defaultSort('order')
            ->columns([
                SpatieMediaLibraryImageColumn::make('image')->collection('image')->label('')->imageSize(64),
                TextColumn::make('title')
                    ->label('Judul')
                    ->searchable()
                    ->description(fn ($record) => $record->subtitle),
                TextColumn::make('link')
                    ->label('Tautan')
                    ->url(fn ($record) => $record->link, true)
                    ->color('primary')
                    ->placeholder('—')
                    ->limit(40),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('Status aktif'),
            ])
            ->recordActions([
                Action::make('duplicate')
                    ->label('Duplikat')
                    ->icon('heroicon-m-document-duplicate')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalHeading('Duplikat slider ini?')
                    ->modalDescription('Salinan dibuat nonaktif dengan judul bertanda "(salinan)" — aktifkan setelah disunting agar tidak tampil dobel.')
                    ->modalSubmitActionLabel('Duplikat')
                    ->action(function (Slider $record) {
                        $copy = Duplicator::slider($record);

                        Notification::make()
                            ->success()
                            ->title('Slider diduplikat')
                            ->actions([
                                NotificationAction::make('edit')
                                    ->label('Sunting salinan')
                                    ->url(SliderResource::getUrl('edit', ['record' => $copy]))
                                    ->button(),
                            ])
                            ->send();
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
