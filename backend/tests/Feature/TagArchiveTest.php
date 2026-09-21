<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Redirect;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class TagArchiveTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('public-api');
    }

    private function publishedPost(): Post
    {
        return Post::create([
            'title' => 'Berita '.fake()->unique()->slug(),
            'body' => 'isi',
            'status' => Post::STATUS_PUBLISHED,
            'published_at' => now()->subDay(),
            'user_id' => User::factory()->create()->id,
        ]);
    }

    public function test_tag_show_returns_name_and_description(): void
    {
        $tag = Tag::create(['name' => 'Prestasi', 'description' => 'Kabar juara siswa.']);

        $this->getJson('/api/v1/tags/'.$tag->slug)
            ->assertOk()
            ->assertJsonPath('name', 'Prestasi')
            ->assertJsonPath('slug', 'prestasi')
            ->assertJsonPath('description', 'Kabar juara siswa.');
    }

    public function test_tags_index_carries_description(): void
    {
        $tag = Tag::create(['name' => 'Prestasi', 'description' => 'Kabar juara.']);
        $this->publishedPost()->tags()->attach($tag);

        $res = $this->getJson('/api/v1/tags');

        $res->assertOk();
        $this->assertSame('Kabar juara.', collect($res->json())->firstWhere('slug', 'prestasi')['description']);
    }

    public function test_posts_filter_by_tag_slug(): void
    {
        $tag = Tag::create(['name' => 'Prestasi']);
        $a = $this->publishedPost();
        $a->tags()->attach($tag);
        $this->publishedPost(); // tanpa tag

        $this->getJson('/api/v1/posts?tag='.$tag->slug)
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_renaming_tag_slug_creates_301_redirect(): void
    {
        $tag = Tag::create(['name' => 'Prestasi']);
        $this->assertSame('prestasi', $tag->slug);

        $tag->update(['slug' => 'prestasi-siswa']);

        $this->assertDatabaseHas('redirects', [
            'from_path' => '/berita/tag/prestasi',
            'to_path' => '/berita/tag/prestasi-siswa',
            'status' => 301,
        ]);
    }

    public function test_resolve_endpoint_returns_tag_redirect(): void
    {
        Redirect::create([
            'from_path' => '/berita/tag/lama',
            'to_path' => '/berita/tag/baru',
            'status' => 301,
            'source' => 'slug-change',
        ]);

        $this->getJson('/api/v1/resolve?path=/berita/tag/lama')
            ->assertOk()
            ->assertJsonPath('to', '/berita/tag/baru')
            ->assertJsonPath('status', 301);
    }
}
