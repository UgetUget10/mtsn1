<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Post;
use App\Notifications\NewCommentPosted;
use App\Support\CommentModeration;
use App\Support\DiscussionSettings;
use App\Support\EditorialStaff;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

/**
 * Komentar publik ala WordPress: daftar komentar yang sudah disetujui +
 * pengiriman komentar baru (masuk antrean moderasi kecuali auto-approve).
 */
class CommentController extends Controller
{
    /**
     * Daftar komentar disetujui untuk sebuah artikel, sudah berulir
     * (parent → replies) dan diurut terlama dulu seperti tampilan WP.
     */
    public function index(Post $post)
    {
        abort_unless($post->isViewablePublicly(), 404);

        $all = $post->comments()
            ->approved()
            ->with('author:id,name')
            ->orderBy('created_at')
            ->get();

        return response()->json([
            'open' => $post->commentsAreOpen(),
            'require_email' => DiscussionSettings::requireEmail(),
            'subscriptions_enabled' => DiscussionSettings::subscriptionsEnabled(),
            'count' => $all->count(),
            'data' => $this->thread($all, null),
        ]);
    }

    /**
     * Kirim komentar baru. Honeypot + validasi + rate-limit (route). Status awal
     * mengikuti config('editorial.comments.auto_approve').
     */
    public function store(Request $request, Post $post)
    {
        abort_unless($post->isViewablePublicly(), 404);

        if (! $post->commentsAreOpen()) {
            throw ValidationException::withMessages([
                'body' => 'Komentar untuk artikel ini sudah ditutup.',
            ]);
        }

        // Honeypot: field "website" disembunyikan lewat CSS. Bot mengisinya.
        if (filled($request->input('website'))) {
            return response()->json(['message' => 'Komentar Anda terkirim dan menunggu moderasi.'], 201);
        }

        $data = $request->validate([
            'author_name' => ['required', 'string', 'max:100'],
            'author_email' => [DiscussionSettings::requireEmail() ? 'required' : 'nullable', 'email:rfc', 'max:150'],
            'author_url' => ['nullable', 'url', 'max:200'],
            'body' => ['required', 'string', 'min:3', 'max:5000'],
            'parent_id' => ['nullable', 'integer'],
            // wp: "Notify me of follow-up comments by email". Hanya berarti bila
            // email diisi & fitur langganan diaktifkan admin.
            'subscribe' => ['sometimes', 'boolean'],
        ]);

        $parent = null;
        if (! empty($data['parent_id'])) {
            $parent = $post->comments()->approved()->find($data['parent_id']);
            abort_unless($parent, 422, 'Komentar yang dibalas tidak ditemukan.');

            // Batasi kedalaman berulir (wp: thread_comments_depth) — balasan
            // terhadap balasan terdalam ditarik naik ke level maksimum.
            $depth = 1;
            for ($p = $parent; $p->parent_id; $p = $p->parent) {
                $depth++;
            }
            if ($depth >= DiscussionSettings::maxDepth()) {
                $data['parent_id'] = $parent->parent_id ?: $parent->id;
            }
        }

        $name = strip_tags($data['author_name']);
        $body = strip_tags($data['body']);
        $ip = $request->ip();

        // wp: Settings → Discussion — comment_max_links, Comment Moderation keys,
        // Disallowed Comment Keys. Menentukan status awal (approved/pending/spam).
        $status = CommentModeration::initialStatus([
            'author_name' => $name,
            'author_email' => $data['author_email'] ?? '',
            'author_url' => $data['author_url'] ?? '',
            'author_ip' => (string) $ip,
            'body' => $body,
        ]);

        // Langganan hanya masuk akal bila fitur aktif & ada email tujuannya.
        $subscribe = ($data['subscribe'] ?? false)
            && DiscussionSettings::subscriptionsEnabled()
            && filled($data['author_email'] ?? null);

        $comment = $post->comments()->create([
            'parent_id' => $data['parent_id'] ?? null,
            'author_name' => $name,
            'author_email' => $data['author_email'] ?? null,
            'author_url' => $data['author_url'] ?? null,
            'author_ip' => $ip,
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
            'body' => $body,
            'status' => $status,
            'subscribed' => $subscribe,
        ]);

        $autoApprove = $status === Comment::STATUS_APPROVED;

        // Komentar yang otomatis ditandai spam: terima diam-diam (jangan beri
        // umpan balik yang bisa dipakai spammer menyetel pesan), jangan
        // ganggu editor dengan notifikasi.
        if ($status !== Comment::STATUS_SPAM) {
            $staff = EditorialStaff::all();
            if ($staff->isNotEmpty()) {
                Notification::send($staff, new NewCommentPosted($comment));
            }
        }

        return response()->json([
            'message' => $autoApprove
                ? 'Komentar Anda telah tayang. Terima kasih!'
                : 'Komentar Anda terkirim dan menunggu moderasi.',
            'approved' => $autoApprove,
        ], 201);
    }

    /**
     * Berhenti langganan balasan komentar lewat token (tanpa login) —
     * dipanggil dari tautan di kaki email CommentReplyPosted.
     */
    public function unsubscribe(string $token)
    {
        $comment = Comment::where('unsubscribe_token', $token)->first();

        // Idempoten: token tak dikenal / sudah dipakai tetap balas sukses
        // supaya tautan email tak pernah menampilkan error yang membingungkan.
        if ($comment && $comment->subscribed) {
            $comment->forceFill(['subscribed' => false])->saveQuietly();
        }

        return response()->json([
            'message' => 'Anda tidak akan lagi menerima email balasan untuk komentar ini.',
        ]);
    }

    /**
     * Susun daftar datar menjadi pohon.
     *
     * @param  Collection<int, Comment>  $all
     * @return array<int, array<string, mixed>>
     */
    private function thread($all, ?int $parentId): array
    {
        return $all
            ->where('parent_id', $parentId)
            ->map(fn (Comment $c) => [
                'id' => $c->id,
                'author_name' => $c->author?->name ?? $c->author_name,
                'author_url' => $c->author_url,
                'is_staff' => $c->user_id !== null,
                'body' => $c->body,
                'created_at' => $c->created_at?->toIso8601String(),
                'replies' => $this->thread($all, $c->id),
            ])
            ->values()
            ->all();
    }
}
