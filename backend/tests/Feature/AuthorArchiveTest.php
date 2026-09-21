<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AuthorArchiveTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('public-api');
    }

    private function author(array $attrs = []): User
    {
        return User::factory()->create(array_merge([
            'name' => 'Laras Padmasari',
            'show_publicly' => true,
        ], $attrs));
    }

    private function publishedPostBy(User $u): Post
    {
        return Post::create([
            'title' => 'Berita '.fake()->unique()->slug(),
            'body' => 'isi',
            'status' => Post::STATUS_PUBLISHED,
            'published_at' => now()->subDay(),
            'user_id' => $u->id,
        ]);
    }

    public function test_user_gets_slug_on_create(): void
    {
        $u = $this->author();
        $this->assertSame('laras-padmasari', $u->slug);
    }

    public function test_author_show_returns_profile_and_post_count(): void
    {
        $u = $this->author(['job_title' => 'Redaktur', 'bio' => 'Halo.', 'social' => ['website' => 'https://x.test']]);
        $this->publishedPostBy($u);
        $this->publishedPostBy($u);

        $res = $this->getJson('/api/v1/authors/'.$u->slug);

        $res->assertOk()
            ->assertJsonPath('name', 'Laras Padmasari')
            ->assertJsonPath('job_title', 'Redaktur')
            ->assertJsonPath('bio', 'Halo.')
            ->assertJsonPath('posts_count', 2)
            ->assertJsonPath('social.website', 'https://x.test');
    }

    public function test_author_without_published_posts_is_404(): void
    {
        $u = $this->author();

        $this->getJson('/api/v1/authors/'.$u->slug)->assertNotFound();
    }

    public function test_private_author_is_404_even_with_posts(): void
    {
        $u = $this->author(['show_publicly' => false]);
        $this->publishedPostBy($u);

        $this->getJson('/api/v1/authors/'.$u->slug)->assertNotFound();
    }

    public function test_posts_endpoint_filters_by_author_slug(): void
    {
        $a = $this->author(['name' => 'Penulis A']);
        $b = $this->author(['name' => 'Penulis B']);
        $this->publishedPostBy($a);
        $this->publishedPostBy($a);
        $this->publishedPostBy($b);

        $res = $this->getJson('/api/v1/posts?author='.$a->slug);

        $res->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_post_resource_exposes_author_object_with_public_slug(): void
    {
        $u = $this->author(['job_title' => 'Guru']);
        $post = $this->publishedPostBy($u);

        $res = $this->getJson('/api/v1/posts/'.$post->slug);

        $res->assertOk()
            ->assertJsonPath('data.author.name', 'Laras Padmasari')
            ->assertJsonPath('data.author.slug', $u->slug)
            ->assertJsonPath('data.author.job_title', 'Guru');
    }

    public function test_author_slug_null_in_post_when_profile_private(): void
    {
        $u = $this->author(['show_publicly' => false]);
        $post = $this->publishedPostBy($u);

        $this->getJson('/api/v1/posts/'.$post->slug)
            ->assertOk()
            ->assertJsonPath('data.author.name', 'Laras Padmasari')
            ->assertJsonPath('data.author.slug', null);
    }
}
