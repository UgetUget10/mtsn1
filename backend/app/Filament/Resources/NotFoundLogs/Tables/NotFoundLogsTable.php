<?php

namespace App\Filament\Resources\NotFoundLogs\Tables;

use App\Models\NotFoundLog;
use App\Models\Page;
use App\Models\Post;
use App\Models\Redirect;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class NotFoundLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('path')
                    ->label('URL yang dicari')
                    ->searchable()
                    ->copyable()
                    ->weight('bold')
                    ->url(fn (NotFoundLog $r) => rtrim(config('services.frontend.url') ?: config('app.url'), '/').$r->path)
                    ->openUrlInNewTab(),
                TextColumn::make('hits')
                    ->label('Diakses')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color(fn (int $state) => $state >= 10 ? 'danger' : ($state >= 3 ? 'warning' : 'gray')),
                TextColumn::make('referrer')
                    ->label('Dari')
                    ->placeholder('— (langsung)')
                    ->limit(40)
                    ->tooltip(fn (NotFoundLog $r) => $r->referrer)
                    ->toggleable(),
                TextColumn::make('last_seen_at')
                    ->label('Terakhir dilihat')
                    ->since()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->state(fn (NotFoundLog $r) => match (true) {
                        $r->resolved_at !== null => 'Sudah dialihkan',
                        $r->ignored_at !== null => 'Diabaikan',
                        default => 'Belum ditangani',
                    })
                    ->color(fn (string $state) => match ($state) {
                        'Sudah dialihkan' => 'success',
                        'Diabaikan' => 'gray',
                        default => 'danger',
                    }),
            ])
            ->defaultSort('last_seen_at', 'desc')
            ->recordActions([
                ActionGroup::make([
                    Action::make('createRedirect')
                        ->label('Buatkan pengalihan')
                        ->icon('heroicon-o-arrows-right-left')
                        ->color('success')
                        ->visible(fn (NotFoundLog $r) => $r->resolved_at === null)
                        ->fillForm(fn (NotFoundLog $r) => ['from_path' => $r->path, 'to_path' => '/'])
                        ->schema([
                            TextInput::make('from_path')
                                ->label('Dari')
                                ->disabled()
                                ->dehydrated(),
                            Select::make('target_type')
                                ->label('Arahkan ke')
                                ->options([
                                    'post' => 'Berita',
                                    'page' => 'Halaman profil',
                                    'custom' => 'URL lain (ketik manual)',
                                ])
                                ->default('post')
                                ->live()
                                ->selectablePlaceholder(false)
                                ->native(false),
                            Select::make('target_post')
                                ->label('Pilih berita')
                                ->visible(fn ($get) => $get('target_type') === 'post')
                                ->options(fn () => Post::query()->orderByDesc('published_at')->limit(200)
                                    ->pluck('title', 'slug'))
                                ->searchable(),
                            Select::make('target_page')
                                ->label('Pilih halaman')
                                ->visible(fn ($get) => $get('target_type') === 'page')
                                ->options(fn () => Page::query()->orderBy('title')->pluck('title', 'slug'))
                                ->searchable(),
                            TextInput::make('to_path')
                                ->label('Ke path')
                                ->visible(fn ($get) => $get('target_type') === 'custom')
                                ->helperText('Contoh: /berita/judul-baru atau /profil/sejarah')
                                ->prefix(config('app.url')),
                        ])
                        ->action(function (NotFoundLog $r, array $data) {
                            $to = match ($data['target_type']) {
                                'post' => $data['target_post'] ? '/berita/'.$data['target_post'] : null,
                                'page' => $data['target_page'] ? '/profil/'.$data['target_page'] : null,
                                default => $data['to_path'] ?: null,
                            };

                            if (! $to) {
                                Notification::make()->title('Tujuan belum dipilih.')->danger()->send();

                                return;
                            }

                            Redirect::updateOrCreate(
                                ['from_path' => $r->path],
                                ['to_path' => '/'.ltrim($to, '/'), 'status' => 301, 'source' => 'manual'],
                            );

                            $r->update(['resolved_at' => now()]);

                            Notification::make()
                                ->title('Pengalihan dibuat')
                                ->body($r->path.'  →  '.$to)
                                ->success()
                                ->send();
                        }),

                    Action::make('ignore')
                        ->label('Abaikan')
                        ->icon('heroicon-o-eye-slash')
                        ->color('gray')
                        ->visible(fn (NotFoundLog $r) => $r->ignored_at === null && $r->resolved_at === null)
                        ->action(fn (NotFoundLog $r) => $r->update(['ignored_at' => now()])),

                    Action::make('restore')
                        ->label('Tandai belum ditangani')
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->visible(fn (NotFoundLog $r) => $r->ignored_at !== null || $r->resolved_at !== null)
                        ->action(fn (NotFoundLog $r) => $r->update(['ignored_at' => null, 'resolved_at' => null])),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    \Filament\Actions\BulkAction::make('ignoreBulk')
                        ->label('Abaikan')
                        ->icon('heroicon-o-eye-slash')
                        ->action(fn (Collection $records) => $records->each->update(['ignored_at' => now()]))
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
