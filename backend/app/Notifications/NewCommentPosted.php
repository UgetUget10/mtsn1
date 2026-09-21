<?php

namespace App\Notifications;

use App\Models\Comment;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * Dikirim ke editor & super_admin saat komentar pengunjung baru masuk —
 * setara e-mail "Please moderate" WordPress.
 */
class NewCommentPosted extends Notification
{
    use Queueable;

    public function __construct(private readonly Comment $comment) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        $pending = $this->comment->status === Comment::STATUS_PENDING;
        $snippet = Str::limit(strip_tags($this->comment->body), 80);
        $on = $this->comment->commentable?->title ?? 'sebuah artikel';

        return FilamentNotification::make()
            ->title($pending ? 'Komentar menunggu moderasi' : 'Komentar baru')
            ->body("{$this->comment->author_name} pada \"{$on}\": \"{$snippet}\"")
            ->icon('heroicon-o-chat-bubble-left-right')
            ->color($pending ? 'warning' : 'info')
            ->actions([
                Action::make('moderate')
                    ->label('Moderasi')
                    ->url(route('filament.admin.resources.comments.index'))
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }
}
