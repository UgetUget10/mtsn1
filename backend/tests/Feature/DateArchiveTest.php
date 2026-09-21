<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class DateArchiveTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('public-api');
    }

    private function mkPost(string $title, string $publishedAt, array $attrs = []): Post
    {
        return Post::create(array_merge([
            'title' => $title,
            'body' => '<p>Isi.</p>',
            'status' => Post::STATUS_PUBLISHED,
            'published_at' => $publishedAt,
            'user_id' => User::factory()->create()->id,
        ], $attrs));
    }

    public function test_archive_index_groups_published_posts_by_month(): void
    {
        $this->mkPost('A', '2026-07-05 08:00:00');
        $this->mkPost('B', '2026-07-20 08:00:00');
        $this->mkPost('C', '2026-06-11 08:00:00');

        $res = $this->getJson('/api/v1/archives')->assertOk();

        $res->assertJsonPath('0.year', 2026)
            ->assertJsonPath('0.month', 7)
            ->assertJsonPath('0.posts_count', 2)
            ->assertJsonPath('1.month', 6)
            ->assertJsonPath('1.posts_count', 1);
    }

    public function test_archive_index_ignores_drafts_and_private_posts(): void
    {
        $this->mkPost('Terbit', '2026-07-05 08:00:00');
        $this->mkPost('Draft', '2026-07-06 08:00:00', ['status' => Post::STATUS_DRAFT]);
        $this->mkPost('Privat', '2026-07-07 08:00:00', ['visibility' => 'private']);

        $this->getJson('/api/v1/archives')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.posts_count', 1);
    }

    public function test_archive_show_returns_header_for_month_with_posts(): void
    {
        $this->mkPost('A', '2026-07-05 08:00:00');

        $this->getJson('/api/v1/archives/2026/7')
            ->assertOk()
            ->assertJsonPath('year', 2026)
            ->assertJsonPath('month', 7)
            ->assertJsonPath('posts_count', 1);
    }

    public function test_archive_show_404s_for_empty_or_invalid_month(): void
    {
        $this->mkPost('A', '2026-07-05 08:00:00');

        $this->getJson('/api/v1/archives/2026/10')->assertNotFound();
        $this->getJson('/api/v1/archives/2026/13')->assertNotFound();
    }

    public function test_posts_index_filters_by_year_and_month(): void
    {
        $juli = $this->mkPost('Juli', '2026-07-05 08:00:00');
        $this->mkPost('Juni', '2026-06-11 08:00:00');

        $this->getJson('/api/v1/posts?year=2026&month=7')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', $juli->slug);

        $this->getJson('/api/v1/posts?year=2026')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_sticky_posts_do_not_float_on_date_archive(): void
    {
        $sticky = $this->mkPost('Disorot', '2026-07-01 08:00:00', ['is_featured' => true]);
        $newer = $this->mkPost('Terbaru', '2026-07-20 08:00:00');

        $this->getJson('/api/v1/posts?year=2026&month=7')
            ->assertOk()
            ->assertJsonPath('data.0.slug', $newer->slug)
            ->assertJsonPath('data.1.slug', $sticky->slug);
    }
}
