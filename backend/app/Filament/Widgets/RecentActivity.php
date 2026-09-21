<?php

namespace App\Filament\Widgets;

use App\Models\Post;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/**
 * "Aktivitas" — meniru widget Activity dashboard WordPress: konten yang baru
 * diubah/diajukan, dengan penekanan pada yang butuh perhatian editor
 * (menunggu tinjauan, terjadwal). Hanya berita — tipe konten paling dinamis.
 */
class RecentActivity extends TableWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Post::query()
                    ->with('author')
                    ->latest('updated_at')
                    ->limit(10)
            )
            ->heading('Aktivitas terbaru')
            ->columns([
                TextColumn::make('title')
                    ->label('Judul')
                    ->limit(45)
                    ->weight('bold')
                    ->description(fn (Post $r) => $r->author?->name
                        ? 'oleh '.$r->author->name
                        : null),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        Post::STATUS_DRAFT => 'Draft',
                        Post::STATUS_PENDING => 'Menunggu tinjauan',
                        Post::STATUS_SCHEDULED => 'Terjadwal',
                        Post::STATUS_PUBLISHED => 'Terbit',
                        default => $state,
                    })
                    ->color(fn (string $state) => match ($state) {
                        Post::STATUS_PENDING => 'warning',
                        Post::STATUS_SCHEDULED => 'info',
                        Post::STATUS_PUBLISHED => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('published_at')
                    ->label('Tayang')
                    ->dateTime('d M Y H:i')
                    ->placeholder('—')
                    ->since()
                    ->color(fn (Post $r) => $r->status === Post::STATUS_SCHEDULED ? 'info' : null),
                TextColumn::make('updated_at')
                    ->label('Diubah')
                    ->since()
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('edit')
                    ->label('Sunting')
                    ->icon('heroicon-m-pencil-square')
                    ->url(fn (Post $record) => route(
                        'filament.admin.resources.posts.edit',
                        ['record' => $record],
                    )),
            ])
            ->paginated(false);
    }
}
