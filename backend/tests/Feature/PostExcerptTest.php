<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class PostExcerptTest extends TestCase
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
            'status' => Post::STATUS_PUBLISHED,
            'published_at' => now()->subDay(),
            'user_id' => User::factory()->create()->id,
        ], $attrs));
    }

    public function test_manual_excerpt_is_used_verbatim(): void
    {
        $post = $this->mkPost([
            'excerpt' => 'Ringkasan manual.',
            'body' => '<p>Isi panjang lain.</p>',
        ]);

        $this->assertSame('Ringkasan manual.', $post->displayExcerpt());
    }

    public function test_excerpt_auto_generated_from_body_when_blank(): void
    {
        $body = '<p>'.str_repeat('kata ', 80).'</p>';
        $post = $this->mkPost(['body' => $body]);

        $ex = $post->displayExcerpt(10);
        $this->assertStringEndsWith(' …', $ex);
        $this->assertSame(10, substr_count(trim(str_replace(' …', '', $ex)), ' ') + 1);
        $this->assertStringNotContainsString('<p>', $ex);
    }

    public function test_more_tag_splits_the_excerpt(): void
    {
        $post = $this->mkPost([
            'body' => '<p>Bagian teaser.</p><!--more--><p>Bagian sisanya yang panjang sekali.</p>',
        ]);

        $this->assertSame('Bagian teaser.', $post->displayExcerpt());
        $this->assertTrue($post->hasMoreTag());
    }

    public function test_api_detail_strips_more_tag_from_body(): void
    {
        $post = $this->mkPost([
            'body' => '<p>Awal.</p><!--more--><p>Lanjutan.</p>',
        ]);

        $res = $this->getJson("/api/v1/posts/{$post->slug}")->assertOk();
        $body = $res->json('data.body');

        $this->assertStringNotContainsString('more', strtolower($body));
        $this->assertStringContainsString('Awal.', $body);
        $this->assertStringContainsString('Lanjutan.', $body);
    }

    public function test_api_list_exposes_auto_excerpt(): void
    {
        $this->mkPost(['body' => '<p>'.str_repeat('halo ', 100).'</p>']);

        $ex = $this->getJson('/api/v1/posts')->assertOk()->json('data.0.excerpt');
        $this->assertNotEmpty($ex);
        $this->assertStringEndsWith(' …', $ex);
    }
}
