<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AdjacentPostTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('public-api');
    }

    private function mkPost(string $title, string $publishedAt): Post
    {
        return Post::create([
            'title' => $title,
            'body' => '<p>Isi.</p>',
            'status' => Post::STATUS_PUBLISHED,
            'published_at' => $publishedAt,
            'user_id' => User::factory()->create()->id,
        ]);
    }

    public function test_detail_exposes_previous_and_next_by_publish_date(): void
    {
        $older = $this->mkPost('Lama', '2026-01-01 08:00:00');
        $middle = $this->mkPost('Tengah', '2026-02-01 08:00:00');
        $newer = $this->mkPost('Baru', '2026-03-01 08:00:00');

        $this->getJson("/api/v1/posts/{$middle->slug}")
            ->assertOk()
            ->assertJsonPath('data.adjacent.previous.slug', $older->slug)
            ->assertJsonPath('data.adjacent.next.slug', $newer->slug);
    }

    public function test_endpoints_at_the_ends_have_null_neighbours(): void
    {
        $older = $this->mkPost('Lama', '2026-01-01 08:00:00');
        $newer = $this->mkPost('Baru', '2026-03-01 08:00:00');

        $this->getJson("/api/v1/posts/{$older->slug}")
            ->assertJsonPath('data.adjacent.previous', null)
            ->assertJsonPath('data.adjacent.next.slug', $newer->slug);

        $this->getJson("/api/v1/posts/{$newer->slug}")
            ->assertJsonPath('data.adjacent.previous.slug', $older->slug)
            ->assertJsonPath('data.adjacent.next', null);
    }

    public function test_adjacent_skips_drafts_and_private_posts(): void
    {
        $older = $this->mkPost('Lama', '2026-01-01 08:00:00');
        $this->mkPost('Draft', '2026-02-01 08:00:00')->update(['status' => Post::STATUS_DRAFT]);
        $this->mkPost('Privat', '2026-02-15 08:00:00')->update(['visibility' => 'private']);
        $newer = $this->mkPost('Baru', '2026-03-01 08:00:00');

        $this->getJson("/api/v1/posts/{$older->slug}")
            ->assertJsonPath('data.adjacent.next.slug', $newer->slug);
    }

    public function test_list_index_is_not_given_adjacent_field(): void
    {
        $this->mkPost('Satu', '2026-01-01 08:00:00');

        $this->getJson('/api/v1/posts')
            ->assertOk()
            ->assertJsonMissingPath('data.0.adjacent');
    }

    public function test_sticky_posts_float_to_top_of_unfiltered_feed(): void
    {
        $this->mkPost('Biasa lama', '2026-01-01 08:00:00');
        $sticky = $this->mkPost('Disorot', '2026-01-02 08:00:00');
        $sticky->update(['is_featured' => true]);
        $this->mkPost('Biasa baru', '2026-03-01 08:00:00');

        $this->getJson('/api/v1/posts')
            ->assertOk()
            ->assertJsonPath('data.0.slug', $sticky->slug);
    }

    public function test_sticky_posts_do_not_float_on_filtered_archive(): void
    {
        $author = User::factory()->create();
        $author->forceFill(['slug' => 'penulis-uji'])->saveQuietly();

        $sticky = Post::create([
            'title' => 'Disorot',
            'body' => '<p>x</p>',
            'status' => Post::STATUS_PUBLISHED,
            'published_at' => '2026-01-01 08:00:00',
            'is_featured' => true,
            'user_id' => $author->id,
        ]);
        $newer = Post::create([
            'title' => 'Terbaru penulis',
            'body' => '<p>x</p>',
            'status' => Post::STATUS_PUBLISHED,
            'published_at' => '2026-03-01 08:00:00',
            'user_id' => $author->id,
        ]);

        $this->getJson('/api/v1/posts?author=penulis-uji')
            ->assertOk()
            ->assertJsonPath('data.0.slug', $newer->slug)
            ->assertJsonPath('data.1.slug', $sticky->slug);
    }
}
