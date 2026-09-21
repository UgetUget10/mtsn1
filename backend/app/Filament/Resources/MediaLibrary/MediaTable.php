<?php

namespace App\Filament\Resources\MediaLibrary;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('thumb')
                    ->label('')
                    ->square()
                    ->size(48)
                    ->getStateUsing(fn (Media $r) => str_starts_with((string) $r->mime_type, 'image/')
                        ? ($r->hasGeneratedConversion('thumb') ? $r->getUrl('thumb') : $r->getUrl())
                        : null)
                    ->defaultImageUrl(fn () => null),
                TextColumn::make('name')
                    ->label('Judul')
                    ->searchable()
                    ->description(fn (Media $r) => $r->file_name),
                TextColumn::make('custom_properties.alt')
                    ->label('Alt text')
                    ->placeholder('— belum diisi —')
                    ->color(fn (Media $r) => blank($r->getCustomProperty('alt'))
                        && str_starts_with((string) $r->mime_type, 'image/') ? 'warning' : null)
                    ->limit(40)
                    ->wrap(),
                TextColumn::make('model_type')
                    ->label('Dipakai oleh')
                    ->formatStateUsing(fn (?string $state, Media $r) => $state
                        ? class_basename($state).' #'.$r->model_id
                        : '—')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('mime_type')->label('Tipe')->badge()->toggleable(),
                TextColumn::make('size')
                    ->label('Ukuran')
                    ->formatStateUsing(fn (?int $s) => $s ? round($s / 1024).' KB' : '—')
                    ->sortable(),
                TextColumn::make('created_at')->label('Diunggah')->dateTime('d M Y')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Filter::make('type')
                    ->schema([
                        Select::make('type')
                            ->label('Jenis berkas')
                            ->options([
                                'image' => 'Gambar',
                                'application' => 'Dokumen',
                                'video' => 'Video',
                            ]),
                    ])
                    ->query(fn (Builder $q, array $data) => $q->when(
                        $data['type'] ?? null,
                        fn (Builder $q, string $v) => $q->where('mime_type', 'like', $v.'/%'),
                    )),
                SelectFilter::make('collection_name')
                    ->label('Koleksi')
                    ->options(fn () => Media::query()
                        ->distinct()
                        ->orderBy('collection_name')
                        ->pluck('collection_name', 'collection_name')
                        ->all()),
                SelectFilter::make('model_type')
                    ->label('Model')
                    ->options(fn () => Media::query()
                        ->whereNotNull('model_type')
                        ->distinct()
                        ->pluck('model_type')
                        ->mapWithKeys(fn ($t) => [$t => class_basename($t)])
                        ->all()),
            ])
            ->recordActions([
                Action::make('open')
                    ->label('Buka')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->color('gray')
                    ->url(fn (Media $r) => $r->getUrl(), shouldOpenInNewTab: true),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
