<?php

namespace App\Filament\Resources\Comments\Pages;

use App\Filament\Resources\Comments\CommentResource;
use App\Models\Comment;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListComments extends ListRecords
{
    protected static string $resource = CommentResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    /** Tab status ala WordPress: Semua / Menunggu / Disetujui / Spam / Sampah. */
    public function getTabs(): array
    {
        return [
            'all' => Tab::make()
                ->label('Semua')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', '!=', Comment::STATUS_TRASH)),
            'pending' => Tab::make()
                ->label('Menunggu')
                ->badge(fn () => Comment::pending()->count() ?: null)
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', Comment::STATUS_PENDING)),
            'approved' => Tab::make()
                ->label('Disetujui')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', Comment::STATUS_APPROVED)),
            'spam' => Tab::make()
                ->label('Spam')
                ->badge(fn () => Comment::where('status', Comment::STATUS_SPAM)->count() ?: null)
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', Comment::STATUS_SPAM)),
            'trash' => Tab::make()
                ->label('Sampah')
                ->badge(fn () => Comment::where('status', Comment::STATUS_TRASH)->count() ?: null)
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', Comment::STATUS_TRASH)),
        ];
    }
}
