<?php

namespace App\Notifications;

use App\Models\Post;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Dikirim ke editor & super_admin saat kontributor mengajukan post untuk
 * ditinjau (status → pending) — setara notifikasi "pending review" WordPress.
 * Kanal `database` supaya muncul di lonceng panel Filament tanpa perlu email.
 */
class PostPendingReview extends Notification
{
    use Queueable;

    public function __construct(private readonly Post $post, private readonly ?string $submitter) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        $title = $this->post->getRawOriginal('title') ?: 'Tanpa judul';
        $by = $this->submitter ? " oleh {$this->submitter}" : '';

        return FilamentNotification::make()
            ->title('Berita menunggu tinjauan')
            ->body("\"{$title}\" diajukan{$by} dan menunggu untuk ditinjau.")
            ->icon('heroicon-o-inbox-arrow-down')
            ->color('warning')
            ->actions([
                Action::make('review')
                    ->label('Tinjau sekarang')
                    ->url(route('filament.admin.resources.posts.edit', ['record' => $this->post->getKey()]))
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }
}
