<?php

namespace App\Models;

use App\Models\Concerns\TriggersFrontendRevalidation;
use App\Notifications\CommentReplyPosted;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

/**
 * Komentar ala WordPress. Statusnya meniru antrean moderasi WP.
 */
class Comment extends Model
{
    use TriggersFrontendRevalidation;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_SPAM = 'spam';

    public const STATUS_TRASH = 'trash';

    protected $guarded = [];

    protected $casts = [
        'created_at' => 'datetime',
        'subscribed' => 'boolean',
        'reply_notified_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        // Isi token berhenti-langganan begitu pengomentar memilih berlangganan
        // (wp: checkbox "Notify me of follow-up comments").
        static::saving(function (Comment $comment): void {
            if ($comment->subscribed && blank($comment->unsubscribe_token)) {
                $comment->unsubscribe_token = (string) Str::uuid();
            }
        });

        // Saat sebuah komentar BERPINDAH ke "approved" dan ia adalah balasan,
        // beri tahu penulis komentar induk yang berlangganan (wp: follow-up
        // comment notification). Berlaku untuk balasan staf dari panel maupun
        // balasan publik yang baru disetujui moderator / auto-approve.
        static::saved(function (Comment $comment): void {
            $becameApproved = $comment->wasChanged('status') || $comment->wasRecentlyCreated;
            if (! $becameApproved || $comment->status !== self::STATUS_APPROVED || ! $comment->parent_id) {
                return;
            }

            $comment->notifySubscribedAncestor();
        });
    }

    /**
     * Kirim email "ada balasan baru" ke penulis komentar induk terdekat yang
     * berlangganan — asalkan itu bukan balasan dari orang yang sama dan belum
     * pernah diberi tahu untuk balasan ini.
     */
    public function notifySubscribedAncestor(): void
    {
        $parent = $this->parent()->first();
        if (! $parent || ! $parent->subscribed || blank($parent->author_email)) {
            return;
        }

        // Jangan email seseorang soal balasannya sendiri.
        if ($parent->author_email !== null
            && $this->author_email !== null
            && strcasecmp($parent->author_email, $this->author_email) === 0) {
            return;
        }

        // Redam kiriman ganda: satu email per komentar balasan.
        if ($this->reply_notified_at) {
            return;
        }

        Notification::route('mail', $parent->author_email)
            ->notify(new CommentReplyPosted($this, $parent));

        $this->forceFill(['reply_notified_at' => now()])->saveQuietly();
    }

    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /** @return array<string, string> */
    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING => 'Menunggu moderasi',
            self::STATUS_APPROVED => 'Disetujui',
            self::STATUS_SPAM => 'Spam',
            self::STATUS_TRASH => 'Sampah',
        ];
    }
}
