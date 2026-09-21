<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Post;
use App\Models\Setting;
use App\Notifications\NewCommentPosted;
use App\Models\User;
use App\Support\CommentModeration;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * wp: Settings → Discussion — "Hold a comment if it contains N or more links",
 * "Comment Moderation" keys, "Disallowed Comment Keys".
 */
class CommentModerationKeysTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('public-api');
        Notification::fake();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(RoleSeeder::class);
        User::factory()->create()->assignRole('editor'); // penerima NewCommentPosted

        // Auto-approve ON supaya efek filter terlihat jelas (kalau lolos → approved).
        Setting::updateOrCreate(['key' => 'comments_auto_approve'], ['value' => '1']);
        Setting::updateOrCreate(['key' => 'comments_require_email'], ['value' => '0']);
    }

    private function mkPost(): Post
    {
        return Post::create([
            'title' => 'Artikel', 'body' => 'Isi.',
            'status' => Post::STATUS_PUBLISHED, 'published_at' => now()->subDay(),
        ]);
    }

    private function send(Post $post, string $body, array $extra = [])
    {
        return $this->postJson("/api/v1/posts/{$post->slug}/comments", array_merge([
            'author_name' => 'Pengunjung', 'body' => $body,
        ], $extra));
    }

    public function test_clean_comment_is_approved_when_auto_approve_on(): void
    {
        $this->send($this->mkPost(), 'Terima kasih artikelnya bermanfaat.')
            ->assertCreated()
            ->assertJsonPath('approved', true);

        $this->assertSame(Comment::STATUS_APPROVED, Comment::first()->status);
    }

    public function test_comment_with_too_many_links_is_held(): void
    {
        Setting::updateOrCreate(['key' => 'comments_max_links'], ['value' => '2']);

        $this->send($this->mkPost(), 'Lihat https://a.example dan https://b.example ya')
            ->assertCreated()
            ->assertJsonPath('approved', false);

        $this->assertSame(Comment::STATUS_PENDING, Comment::first()->status);
    }

    public function test_one_link_passes_when_threshold_is_two(): void
    {
        Setting::updateOrCreate(['key' => 'comments_max_links'], ['value' => '2']);

        $this->send($this->mkPost(), 'Sumbernya di https://a.example saja')
            ->assertJsonPath('approved', true);
    }

    public function test_moderation_keyword_holds_comment_in_queue(): void
    {
        Setting::updateOrCreate(['key' => 'comments_moderation_keys'], ['value' => "judi\npinjol"]);

        $this->send($this->mkPost(), 'Ada promo PINJOL bunga rendah')
            ->assertCreated()
            ->assertJsonPath('approved', false);

        $this->assertSame(Comment::STATUS_PENDING, Comment::first()->status);
        Notification::assertSentTimes(NewCommentPosted::class, 1); // editor tetap diberi tahu
    }

    public function test_disallowed_keyword_marks_spam_silently(): void
    {
        Setting::updateOrCreate(['key' => 'comments_disallowed_keys'], ['value' => "viagra\nfreemoney"]);

        $this->send($this->mkPost(), 'Beli VIAGRA murah di sini')
            ->assertCreated()
            // Pesan sukses palsu — jangan beri umpan balik ke spammer.
            ->assertJsonPath('approved', false);

        $this->assertSame(Comment::STATUS_SPAM, Comment::first()->status);
        Notification::assertNothingSent(); // editor TIDAK diganggu
    }

    public function test_disallowed_matches_against_author_email_and_ip(): void
    {
        Setting::updateOrCreate(['key' => 'comments_disallowed_keys'], ['value' => 'spammer@bad.example']);

        $this->send($this->mkPost(), 'Komentar biasa', ['author_email' => 'spammer@bad.example'])
            ->assertCreated();

        $this->assertSame(Comment::STATUS_SPAM, Comment::first()->status);
    }

    public function test_disallowed_wins_over_moderation(): void
    {
        Setting::updateOrCreate(['key' => 'comments_moderation_keys'], ['value' => 'promo']);
        Setting::updateOrCreate(['key' => 'comments_disallowed_keys'], ['value' => 'viagra']);

        $this->send($this->mkPost(), 'promo viagra')->assertCreated();

        $this->assertSame(Comment::STATUS_SPAM, Comment::first()->status);
    }

    public function test_unit_initial_status_matrix(): void
    {
        Setting::updateOrCreate(['key' => 'comments_auto_approve'], ['value' => '1']);
        Setting::updateOrCreate(['key' => 'comments_disallowed_keys'], ['value' => 'badword']);
        Setting::updateOrCreate(['key' => 'comments_moderation_keys'], ['value' => 'holdme']);

        $this->assertSame('spam', CommentModeration::initialStatus(['body' => 'ada BADWORD di sini']));
        $this->assertSame('pending', CommentModeration::initialStatus(['body' => 'tolong HOLDME dulu']));
        $this->assertSame('approved', CommentModeration::initialStatus(['body' => 'komentar bersih']));
    }
}
