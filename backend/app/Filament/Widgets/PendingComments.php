<?php

namespace App\Filament\Widgets;

use App\Models\Comment;
use App\Models\Post;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Str;

/**
 * "Komentar menunggu moderasi" — meniru bagian Comments pada widget Activity
 * dashboard WordPress. Antrean pending dengan aksi cepat setujui / spam / tolak,
 * tanpa perlu membuka resource Komentar. Disembunyikan bila antrean kosong,
 * persis perilaku WP.
 */
class PendingComments extends TableWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return Comment::pending()->exists();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Comment::query()
                    ->pending()
                    ->with(['commentable', 'author'])
                    ->latest()
                    ->limit(10)
            )
            ->heading('Komentar menunggu moderasi')
            ->columns([
                TextColumn::make('author_name')
                    ->label('Penulis')
                    ->weight('bold')
                    ->description(fn (Comment $r) => Str::limit(strip_tags((string) $r->body), 80)
                        .' — '.($r->commentable?->title ?? 'artikel dihapus')),
                TextColumn::make('created_at')
                    ->label('Dikirim')
                    ->since(),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('Setujui')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(fn (Comment $r) => $r->update(['status' => Comment::STATUS_APPROVED])),
                Action::make('spam')
                    ->label('Spam')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn (Comment $r) => $r->update(['status' => Comment::STATUS_SPAM])),
                Action::make('trash')
                    ->label('Tolak')
                    ->icon('heroicon-o-trash')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->action(fn (Comment $r) => $r->update(['status' => Comment::STATUS_TRASH])),
                Action::make('open')
                    ->label('Buka artikel')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->visible(fn (Comment $r) => $r->commentable instanceof Post && filled(env('FRONTEND_URL')))
                    ->url(
                        fn (Comment $r) => rtrim((string) env('FRONTEND_URL', ''), '/').'/berita/'.$r->commentable->slug,
                        shouldOpenInNewTab: true,
                    ),
            ])
            ->paginated(false);
    }
}
