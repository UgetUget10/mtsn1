<?php

namespace App\Filament\Resources\Pages\Tables;

use App\Filament\Resources\Pages\PageResource;
use App\Filament\Support\ForceDeleteImpact;
use App\Models\Page;
use App\Support\Content\Duplicator;
use Filament\Actions\Action;
// Aksi di dalam notifikasi Filament v5 memakai Filament\Actions\Action juga.
use Filament\Actions\Action as NotificationAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class PagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->reorderable('order')
            ->defaultSort('order')
            ->columns([
                TextColumn::make('title')
                    ->label('Judul')
                    ->searchable()
                    ->sortable()
                    // Prefiks "— " per tingkat (wp: daftar halaman berjenjang).
                    ->formatStateUsing(function ($state, $record) {
                        $depth = 0;
                        for ($p = $record->parent; $p; $p = $p->parent) {
                            $depth++;
                        }

                        return ($depth ? str_repeat('— ', $depth) : '').$state;
                    }),
                TextColumn::make('parent.title')
                    ->label('Induk')
                    ->placeholder('—')
                    ->color('gray')
                    ->toggleable(),
                TextColumn::make('slug')
                    ->label('Slug')
                    ->color('gray')
                    ->searchable(),
                IconColumn::make('is_published')
                    ->label('Terbit')
                    ->boolean(),
                TextColumn::make('order')
                    ->label('Urutan')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('parent_id')
                    ->label('Halaman induk')
                    ->relationship('parent', 'title'),
                TrashedFilter::make(),
            ])
            ->recordActions([
                // wp: plugin "Duplicate Post" — salin halaman jadi draft baru.
                Action::make('duplicate')
                    ->label('Duplikat')
                    ->icon('heroicon-m-document-duplicate')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalHeading('Duplikat halaman ini?')
                    ->modalDescription('Salinan dibuat sebagai halaman belum terbit dengan judul bertanda "(salinan)". Blok konten ikut disalin.')
                    ->modalSubmitActionLabel('Duplikat')
                    ->action(function (Page $record) {
                        $copy = Duplicator::page($record);

                        Notification::make()
                            ->success()
                            ->title('Halaman diduplikat')
                            ->body('Salinan tersimpan sebagai halaman belum terbit.')
                            ->actions([
                                NotificationAction::make('edit')
                                    ->label('Sunting salinan')
                                    ->url(PageResource::getUrl('edit', ['record' => $copy]))
                                    ->button(),
                            ])
                            ->send();
                    })
                    ->authorize(fn () => auth()->user()?->can('create', Page::class) ?? false),

                // "Quick Edit" ala WordPress — ubah judul/slug/status terbit
                // langsung dari baris tabel tanpa membuka blok konten penuh.
                Action::make('quickEdit')
                    ->label('Sunting cepat')
                    ->icon('heroicon-m-bolt')
                    ->color('gray')
                    ->slideOver()
                    ->modalHeading('Sunting cepat')
                    ->fillForm(fn (Page $record) => [
                        'title' => $record->title,
                        'slug' => $record->slug,
                        'is_published' => $record->is_published,
                    ])
                    ->schema([
                        TextInput::make('title')
                            ->label('Judul')
                            ->required(),
                        TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->unique(table: 'pages', ignoreRecord: true),
                        Toggle::make('is_published')
                            ->label('Terbit'),
                    ])
                    ->action(function (Page $record, array $data) {
                        $record->update($data);

                        Notification::make()->title('Halaman diperbarui')->success()->send();
                    }),

                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make()
                        ->modalDescription(fn (Collection $records) => ForceDeleteImpact::describe($records, [
                            'halaman anak (jadi yatim, tidak ikut terhapus)' => fn (Page $r) => $r->children()->count(),
                        ])),
                ]),
            ]);
    }
}
