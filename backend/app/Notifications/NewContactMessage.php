<?php

namespace App\Notifications;

use App\Models\Contact;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Dikirim ke editor & super_admin saat ada pesan kontak publik baru masuk —
 * agar tidak terlewat (sebelumnya form kontak menyimpan diam-diam).
 */
class NewContactMessage extends Notification
{
    use Queueable;

    public function __construct(private readonly Contact $contact) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        $subject = $this->contact->subject ?: 'Tanpa subjek';

        return FilamentNotification::make()
            ->title('Pesan kontak baru')
            ->body("Dari {$this->contact->name}: \"{$subject}\"")
            ->icon('heroicon-o-envelope')
            ->color('info')
            ->actions([
                Action::make('open')
                    ->label('Buka pesan')
                    ->url(route('filament.admin.resources.contacts.view', ['record' => $this->contact->getKey()]))
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }
}
