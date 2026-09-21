<?php

namespace App\Filament\Resources\Comments\Tables;

use App\Models\Comment;
use App\Models\Post;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class CommentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('author_name')
                    ->label('Penulis')
                    ->description(fn (Comment $r) => $r->author_email)
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('body')
                    ->label('Komentar')
                    ->wrap()
                    ->limit(160)
                    ->searchable(),
                TextColumn::make('commentable_id')
                    ->label('Pada artikel')
                    ->formatStateUsing(fn (Comment $r) => $r->commentable?->title ?? '—')
                    ->limit(30)
                    ->url(fn (Comment $r) => $r->commentable instanceof Post
                        ? route('filament.admin.resources.posts.edit', ['record' => $r->commentable_id])
                        : null)
                    ->toggleable(),
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
                    // wp: Comments → Edit — perbaiki isi / identitas penulis
                    // sebelum menyetujui (mis. rapikan typo, buang tautan spam).
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
                            TextInput::make('author_url')
                                ->label('Situs penulis')
                                ->url()
                                ->maxLength(200),
                            Textarea::make('body')
                                ->label('Isi komentar')
                                ->required()
                                ->rows(5),
                        ])
                        ->mutateFormDataUsing(function (array $data): array {
                            // Konsisten dgn jalur publik (CommentController) —
                            // komentar tersimpan sebagai teks polos.
                            $data['body'] = strip_tags($data['body']);

                            return $data;
                        })
                        ->after(fn (Comment $r) => Notification::make()
                            ->title('Komentar diperbarui')->success()->send()),
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

                            // Menyetujui balasan sekaligus menyetujui komentar induk
                            // (wp: membalas dari layar moderasi meng-approve).
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
                    self::bulkStatus('approveBulk', 'Setujui', Comment::STATUS_APPROVED, 'heroicon-o-check-circle'),
                    self::bulkStatus('spamBulk', 'Tandai spam', Comment::STATUS_SPAM, 'heroicon-o-no-symbol'),
                    self::bulkStatus('trashBulk', 'Pindah ke sampah', Comment::STATUS_TRASH, 'heroicon-o-trash'),
                    DeleteBulkAction::make()->label('Hapus permanen'),
                ]),
            ]);
    }

    private static function bulkStatus(string $name, string $label, string $status, string $icon): BulkAction
    {
        return BulkAction::make($name)
            ->label($label)
            ->icon($icon)
            ->action(fn (Collection $records) => $records->each->update(['status' => $status]))
            ->deselectRecordsAfterCompletion();
    }
}
