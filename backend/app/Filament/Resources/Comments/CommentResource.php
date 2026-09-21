<?php

namespace App\Filament\Resources\Comments;

use App\Filament\Resources\Comments\Pages\ListComments;
use App\Filament\Resources\Comments\Tables\CommentsTable;
use App\Models\Comment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Antrean moderasi komentar ala WordPress (Comments → All / Pending / Spam / Trash).
 * Tidak ada form create — komentar hanya masuk dari frontend; staf memoderasi
 * (setujui / spam / sampah) dan bisa membalas dari sini (balasan auto-approved).
 */
class CommentResource extends Resource
{
    protected static ?string $model = Comment::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static string|UnitEnum|null $navigationGroup = 'Interaksi';

    protected static ?string $navigationLabel = 'Komentar';

    protected static ?string $modelLabel = 'Komentar';

    protected static ?string $pluralModelLabel = 'Komentar';

    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        $count = Comment::pending()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return CommentsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListComments::route('/'),
        ];
    }
}
