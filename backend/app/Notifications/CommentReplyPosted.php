<?php

namespace App\Notifications;

use App\Models\Comment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * Email "ada balasan baru untuk komentar Anda" — setara notifikasi follow-up
 * comment WordPress. Dikirim ke penulis komentar induk yang mencentang
 * "Beri tahu saya bila ada balasan" saat sebuah balasan disetujui.
 */
class CommentReplyPosted extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Comment $reply,
        private readonly Comment $parent,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $post = $this->parent->commentable;
        $title = $post?->title ?? 'sebuah artikel';
        $frontend = rtrim((string) config('services.frontend.url'), '/');
        $postUrl = $post && $frontend ? "{$frontend}/berita/{$post->slug}#komentar-{$this->reply->id}" : $frontend;

        $unsubscribeUrl = $this->parent->unsubscribe_token && $frontend
            ? "{$frontend}/berita/komentar/berhenti-langganan?token={$this->parent->unsubscribe_token}"
            : null;

        $mail = (new MailMessage)
            ->subject("Ada balasan baru untuk komentar Anda di \"{$title}\"")
            ->greeting("Halo {$this->parent->author_name},")
            ->line("{$this->reply->author_name} membalas komentar Anda pada artikel \"{$title}\":")
            ->line('“'.Str::limit(strip_tags($this->reply->body), 300).'”')
            ->action('Lihat balasan', $postUrl);

        if ($unsubscribeUrl) {
            $mail->line('---')
                ->line('Tidak ingin lagi menerima email ini? [Berhenti berlangganan]('.$unsubscribeUrl.') dari balasan komentar ini.');
        }

        return $mail;
    }
}
