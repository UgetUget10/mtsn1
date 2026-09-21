<?php

namespace App\Filament\Resources\Posts\RelationManagers;

use App\Models\Comment;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

/**
 * Moderasi komentar langsung dari halaman edit berita — setara membuka
 * "Comments" tanpa pindah dari konteks artikel yang sedang dikerjakan.
 * Aksi meniru CommentsTable milik CommentResource.
 */
class CommentsRelationManager extends RelationManager
{
    protected static string $relationship = 'comments';

    protected static ?string $title = 'Komentar';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('body')
            ->columns([
                TextColumn::make('author_name')
                    ->label('Penulis')
                    ->description(fn (Comment $r) => $r->author_email)
                    ->weight('bold'),
                TextColumn::make('body')
                    ->label('Komentar')
                    ->wrap()
                    ->limit(160),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => Comment::statuses()[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        Comment::STATUS_APPROVED => 'success',
                        Comment::STATUS_PENDING => 'warning',
                        Comment::STATUS_SPAM => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('created_at')
                    ->label('Dikirim')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(Comment::statuses()),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()
                        ->label('Sunting')
                        ->icon('heroicon-o-pencil-square')
                        ->modalHeading('Sunting komentar')
                        ->schema([
                            TextInput::make('author_name')
                                ->label('Nama penulis')
                                ->required()
                                ->maxLength(100),
                            TextInput::make('author_email')
                                ->label('Email penulis')
                                ->email()
                                ->maxLength(150),
                            Textarea::make('body')
                                ->label('Isi komentar')
                                ->required()
                                ->rows(5),
                        ])
                        ->mutateFormDataUsing(function (array $data): array {
                            $data['body'] = strip_tags($data['body']);

                            return $data;
                        }),
                    Action::make('approve')
                        ->label('Setujui')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn (Comment $r) => $r->status !== Comment::STATUS_APPROVED)
                        ->action(fn (Comment $r) => $r->update(['status' => Comment::STATUS_APPROVED])),
                    Action::make('unapprove')
                        ->label('Batalkan persetujuan')
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->visible(fn (Comment $r) => $r->status === Comment::STATUS_APPROVED)
                        ->action(fn (Comment $r) => $r->update(['status' => Comment::STATUS_PENDING])),
                    Action::make('spam')
                        ->label('Tandai spam')
                        ->icon('heroicon-o-no-symbol')
                        ->color('danger')
                        ->visible(fn (Comment $r) => $r->status !== Comment::STATUS_SPAM)
                        ->action(fn (Comment $r) => $r->update(['status' => Comment::STATUS_SPAM])),
                    Action::make('trash')
                        ->label('Pindah ke sampah')
                        ->icon('heroicon-o-trash')
                        ->color('gray')
                        ->visible(fn (Comment $r) => $r->status !== Comment::STATUS_TRASH)
                        ->action(fn (Comment $r) => $r->update(['status' => Comment::STATUS_TRASH])),
                    Action::make('reply')
                        ->label('Balas')
                        ->icon('heroicon-o-chat-bubble-left')
                        ->schema([
                            Textarea::make('body')->label('Balasan')->required()->rows(4),
                        ])
                        ->action(function (Comment $r, array $data) {
                            $reply = $r->commentable->comments()->create([
                                'parent_id' => $r->id,
                                'user_id' => auth()->id(),
                                'author_name' => auth()->user()->name,
                                'author_email' => auth()->user()->email,
                                'author_ip' => request()->ip(),
                                'body' => strip_tags($data['body']),
                                'status' => Comment::STATUS_APPROVED,
                            ]);

                            if ($r->status !== Comment::STATUS_APPROVED) {
                                $r->update(['status' => Comment::STATUS_APPROVED]);
                            }

                            Notification::make()->title('Balasan terkirim')->success()->send();

                            return $reply;
                        }),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('Hapus permanen'),
                ]),
            ]);
    }

    public function canCreate(): bool
    {
        return false;
    }
}
