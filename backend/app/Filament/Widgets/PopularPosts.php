<?php

namespace App\Filament\Widgets;

use App\Models\Post;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/**
 * "Berita terpopuler" — insight cepat tanpa perlu buka Google Analytics.
 * Kolom `views` (App\Models\Post) adalah PENGHITUNG KUMULATIF sepanjang
 * waktu, bukan log per-kunjungan — tidak ada tabel time-series untuk
 * menghitung "minggu ini" secara jujur. Jadi widget ini me-ranking berita
 * yang TERBIT dalam 30 hari terakhir berdasarkan total dilihat, sebagai
 * proksi "sedang populer" tanpa mengklaim angka per-minggu yang sebenarnya
 * tidak tersedia.
 */
class PopularPosts extends TableWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Post::query()
                    ->where('status', Post::STATUS_PUBLISHED)
                    ->where('published_at', '>=', now()->subDays(30))
                    ->orderByDesc('views')
                    ->limit(10)
            )
            ->heading('Berita terpopuler (terbit 30 hari terakhir)')
            ->columns([
                TextColumn::make('title')
                    ->label('Judul')
                    ->limit(50)
                    ->weight('bold')
                    ->description(fn (Post $r) => $r->category?->name),
                TextColumn::make('views')
                    ->label('Dilihat')
                    ->numeric()
                    ->badge()
                    ->color('success')
                    ->sortable(),
                TextColumn::make('published_at')
                    ->label('Terbit')
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
