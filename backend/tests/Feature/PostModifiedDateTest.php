<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class PostModifiedDateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('public-api');
    }

    private function mkPost(array $attrs = []): Post
    {
        return Post::create(array_merge([
            'title' => 'Judul',
            'body' => '<p>Isi.</p>',
            'status' => Post::STATUS_PUBLISHED,
            'published_at' => now()->subDays(5),
            'user_id' => User::factory()->create()->id,
        ], $attrs));
    }

    public function test_freshly_published_post_reports_no_modified_date(): void
    {
        $post = $this->mkPost(['published_at' => now()]);

        $this->assertFalse($post->wasEditedAfterPublish());
        $this->getJson("/api/v1/posts/{$post->slug}")
            ->assertOk()
            ->assertJsonPath('data.updated_at', null);
    }

    public function test_post_edited_after_publish_reports_modified_date(): void
    {
        $post = $this->mkPost();
        $post->update(['title' => 'Judul Disunting']);

        $this->assertTrue($post->fresh()->wasEditedAfterPublish());
        $this->getJson("/api/v1/posts/{$post->slug}")
            ->assertOk()
            ->assertJsonPath('data.updated_at', fn ($v) => is_string($v) && $v !== '');
    }

    public function test_view_counter_does_not_mark_post_as_modified(): void
    {
        $post = $this->mkPost(['published_at' => now()]);

        // Dua kali baca → views naik, tapi updated_at TIDAK boleh ikut berubah
        // (PostController pakai withoutEvents + query builder).
        $this->getJson("/api/v1/posts/{$post->slug}")->assertOk();
        $this->getJson("/api/v1/posts/{$post->slug}")
            ->assertOk()
            ->assertJsonPath('data.updated_at', null);

        $this->assertSame(2, $post->fresh()->views);
    }
}
