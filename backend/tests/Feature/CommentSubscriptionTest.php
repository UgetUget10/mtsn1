<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Notifications\CommentReplyPosted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * wp core: "Notify me of follow-up comments by email".
 */
class CommentSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('public-api');
        Notification::fake();
        config()->set('editorial.comments.auto_approve', false);
    }

    private function mkPost(): Post
    {
        return Post::create([
            'title' => 'Artikel', 'body' => 'Isi.',
            'status' => Post::STATUS_PUBLISHED, 'published_at' => now()->subDay(),
        ]);
    }

    private function approvedComment(Post $post, array $attrs = []): Comment
    {
        return $post->comments()->create(array_merge([
            'author_name' => 'Ani', 'author_email' => 'ani@example.com',
            'author_ip' => '127.0.0.1', 'body' => 'Komentar induk',
            'status' => Comment::STATUS_APPROVED,
        ], $attrs));
    }

    public function test_store_records_subscription_and_generates_token(): void
    {
        $post = $this->mkPost();

        $this->postJson("/api/v1/posts/{$post->slug}/comments", [
            'author_name' => 'Ani', 'author_email' => 'ani@example.com',
            'body' => 'Halo, komentar pertama.', 'subscribe' => true,
        ])->assertCreated();

        $comment = Comment::first();
        $this->assertTrue($comment->subscribed);
        $this->assertNotNull($comment->unsubscribe_token);
    }

    public function test_subscription_ignored_without_email(): void
    {
        config()->set('editorial.comments.auto_approve', true);
        // require_email default true → matikan supaya email boleh kosong
        \App\Models\Setting::updateOrCreate(['key' => 'comments_require_email'], ['value' => '0']);
        $post = $this->mkPost();

        $this->postJson("/api/v1/posts/{$post->slug}/comments", [
            'author_name' => 'Tanpa Email', 'body' => 'Komentar tanpa email.', 'subscribe' => true,
        ])->assertCreated();

        $this->assertFalse(Comment::first()->subscribed);
    }

    public function test_approved_reply_notifies_subscribed_parent_author(): void
    {
        $post = $this->mkPost();
        $parent = $this->approvedComment($post, ['subscribed' => true, 'unsubscribe_token' => 'tok-123']);

        // Balasan orang lain, langsung approved (mis. auto-approve / balasan staf).
        $post->comments()->create([
            'parent_id' => $parent->id,
            'author_name' => 'Budi', 'author_email' => 'budi@example.com',
            'author_ip' => '127.0.0.1', 'body' => 'Balasan Budi',
            'status' => Comment::STATUS_APPROVED,
        ]);

        Notification::assertSentOnDemand(
            CommentReplyPosted::class,
            fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === 'ani@example.com',
        );
    }

    public function test_reply_pending_then_approved_notifies_once(): void
    {
        $post = $this->mkPost();
        $parent = $this->approvedComment($post, ['subscribed' => true, 'unsubscribe_token' => 'tok-abc']);

        $reply = $post->comments()->create([
            'parent_id' => $parent->id,
            'author_name' => 'Budi', 'author_email' => 'budi@example.com',
            'author_ip' => '127.0.0.1', 'body' => 'Balasan Budi',
            'status' => Comment::STATUS_PENDING,
        ]);
        Notification::assertNothingSent();

        $reply->update(['status' => Comment::STATUS_APPROVED]);
        $reply->update(['status' => Comment::STATUS_APPROVED]); // save lagi, tak boleh dobel

        Notification::assertSentOnDemandTimes(CommentReplyPosted::class, 1);
    }

    public function test_no_notification_when_parent_not_subscribed(): void
    {
        $post = $this->mkPost();
        $parent = $this->approvedComment($post, ['subscribed' => false]);

        $post->comments()->create([
            'parent_id' => $parent->id, 'author_name' => 'Budi', 'author_email' => 'budi@example.com',
            'author_ip' => '127.0.0.1', 'body' => 'Balasan', 'status' => Comment::STATUS_APPROVED,
        ]);

        Notification::assertNothingSent();
    }

    public function test_no_notification_for_reply_by_same_person(): void
    {
        $post = $this->mkPost();
        $parent = $this->approvedComment($post, [
            'author_email' => 'ani@example.com', 'subscribed' => true, 'unsubscribe_token' => 'tok-self',
        ]);

        $post->comments()->create([
            'parent_id' => $parent->id, 'author_name' => 'Ani', 'author_email' => 'ANI@example.com',
            'author_ip' => '127.0.0.1', 'body' => 'Menambahkan', 'status' => Comment::STATUS_APPROVED,
        ]);

        Notification::assertNothingSent();
    }

    public function test_unsubscribe_endpoint_clears_subscription(): void
    {
        $post = $this->mkPost();
        $parent = $this->approvedComment($post, ['subscribed' => true, 'unsubscribe_token' => 'tok-unsub']);

        $this->getJson('/api/v1/comments/unsubscribe/tok-unsub')
            ->assertOk()
            ->assertJsonStructure(['message']);

        $this->assertFalse($parent->fresh()->subscribed);
    }

    public function test_unsubscribe_with_unknown_token_is_idempotent(): void
    {
        $this->getJson('/api/v1/comments/unsubscribe/does-not-exist')->assertOk();
    }

    public function test_index_exposes_subscriptions_enabled_flag(): void
    {
        $post = $this->mkPost();

        $this->getJson("/api/v1/posts/{$post->slug}/comments")
            ->assertOk()
            ->assertJsonPath('subscriptions_enabled', true);
    }
}
