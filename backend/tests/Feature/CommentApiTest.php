<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Post;
use App\Models\Setting;
use App\Models\User;
use App\Support\DiscussionSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class CommentApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('public-api');
        config()->set('editorial.comments.auto_approve', false);
    }

    private function publishedPost(): Post
    {
        return Post::create([
            'title' => 'Artikel Uji',
            'body' => 'Isi artikel.',
            'status' => Post::STATUS_PUBLISHED,
            'published_at' => now()->subDay(),
            'user_id' => User::factory()->create()->id,
        ]);
    }

    public function test_index_only_returns_approved_comments_threaded(): void
    {
        $post = $this->publishedPost();

        $approved = $post->comments()->create([
            'author_name' => 'Budi', 'author_email' => 'b@e.com',
            'body' => 'Komentar utama.', 'status' => Comment::STATUS_APPROVED,
        ]);
        $post->comments()->create([
            'parent_id' => $approved->id, 'author_name' => 'Ani', 'author_email' => 'a@e.com',
            'body' => 'Balasan.', 'status' => Comment::STATUS_APPROVED,
        ]);
        $post->comments()->create([
            'author_name' => 'Spammer', 'author_email' => 's@e.com',
            'body' => 'Menunggu.', 'status' => Comment::STATUS_PENDING,
        ]);

        $res = $this->getJson("/api/v1/posts/{$post->slug}/comments");

        $res->assertOk()
            ->assertJsonPath('open', true)
            ->assertJsonPath('count', 2)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.body', 'Komentar utama.')
            ->assertJsonPath('data.0.replies.0.body', 'Balasan.');
    }

    public function test_store_queues_comment_for_moderation(): void
    {
        $post = $this->publishedPost();

        $res = $this->postJson("/api/v1/posts/{$post->slug}/comments", [
            'author_name' => 'Siti',
            'author_email' => 'siti@example.com',
            'body' => 'Terima kasih atas informasinya.',
        ]);

        $res->assertCreated()->assertJsonPath('approved', false);
        $this->assertDatabaseHas('comments', [
            'commentable_id' => $post->id,
            'author_name' => 'Siti',
            'status' => Comment::STATUS_PENDING,
        ]);
    }

    public function test_store_auto_approves_when_configured(): void
    {
        config()->set('editorial.comments.auto_approve', true);
        $post = $this->publishedPost();

        $this->postJson("/api/v1/posts/{$post->slug}/comments", [
            'author_name' => 'Siti', 'author_email' => 'siti@example.com',
            'body' => 'Komentar langsung tayang.',
        ])->assertCreated()->assertJsonPath('approved', true);

        $this->assertDatabaseHas('comments', ['status' => Comment::STATUS_APPROVED]);
    }

    public function test_honeypot_silently_drops_bot_submission(): void
    {
        $post = $this->publishedPost();

        $this->postJson("/api/v1/posts/{$post->slug}/comments", [
            'author_name' => 'Bot', 'author_email' => 'bot@e.com',
            'body' => 'spam spam', 'website' => 'http://spam.example',
        ])->assertCreated();

        $this->assertDatabaseCount('comments', 0);
    }

    public function test_comments_closed_rejects_submission(): void
    {
        config()->set('editorial.comments.enabled', false);
        $post = $this->publishedPost();

        $this->postJson("/api/v1/posts/{$post->slug}/comments", [
            'author_name' => 'Siti', 'author_email' => 'siti@example.com',
            'body' => 'Halo dunia.',
        ])->assertStatus(422);
    }

    public function test_discussion_settings_row_overrides_config_default(): void
    {
        // Default pabrik (config) = perlu moderasi; admin menyalakan auto-approve.
        Setting::updateOrCreate(
            ['key' => DiscussionSettings::KEY_AUTO_APPROVE],
            ['value' => '1', 'group' => 'discussion'],
        );
        Setting::updateOrCreate(
            ['key' => DiscussionSettings::KEY_ENABLED],
            ['value' => '0', 'group' => 'discussion'],
        );

        $this->assertTrue(DiscussionSettings::autoApprove());
        $this->assertFalse(DiscussionSettings::enabled());

        $post = $this->publishedPost();
        $this->assertFalse($post->commentsAreOpen());
    }

    public function test_per_post_meta_can_close_comments(): void
    {
        $post = $this->publishedPost();
        $this->assertTrue($post->commentsAreOpen());

        $post->update(['meta' => ['comments_closed' => true]]);

        $this->assertFalse($post->fresh()->commentsAreOpen());
    }

    public function test_email_optional_when_discussion_setting_disables_requirement(): void
    {
        Setting::updateOrCreate(
            ['key' => DiscussionSettings::KEY_REQUIRE_EMAIL],
            ['value' => '0', 'group' => 'discussion'],
        );
        $post = $this->publishedPost();

        $this->postJson("/api/v1/posts/{$post->slug}/comments", [
            'author_name' => 'Anon',
            'body' => 'Komentar tanpa email.',
        ])->assertCreated();
    }

    /**
     * Regresi: toggle "Tutup komentar untuk artikel ini" (meta.comments_closed)
     * di PostForm harus benar-benar sampai ke frontend. Komponen
     * frontend/src/components/comments.tsx bercabang pada `thread.open` untuk
     * mengganti form dengan pesan "Komentar untuk artikel ini ditutup."
     */
    public function test_closing_comments_on_a_post_is_reflected_in_the_api(): void
    {
        $post = $this->publishedPost();
        $post->forceFill(['meta' => ['comments_closed' => true]])->saveQuietly();

        $this->getJson("/api/v1/posts/{$post->slug}/comments")
            ->assertOk()
            ->assertJsonPath('open', false);

        // Detail artikel memakai flag yang sama.
        $this->getJson("/api/v1/posts/{$post->slug}")
            ->assertOk()
            ->assertJsonPath('data.comments_open', false);
    }

    public function test_posting_a_comment_is_rejected_when_comments_are_closed(): void
    {
        $post = $this->publishedPost();
        $post->forceFill(['meta' => ['comments_closed' => true]])->saveQuietly();

        $this->postJson("/api/v1/posts/{$post->slug}/comments", [
            'author_name' => 'Anon',
            'author_email' => 'anon@contoh.test',
            'body' => 'Seharusnya ditolak.',
        ])->assertStatus(422);

        $this->assertSame(0, $post->comments()->count());
    }
}
