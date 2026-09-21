<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class PostVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('public-api');
    }

    private function makePost(array $attrs = []): Post
    {
        return Post::create(array_merge([
            'title' => 'Judul',
            'body' => '<p>Isi rahasia.</p>',
            'status' => Post::STATUS_PUBLISHED,
            'published_at' => now()->subDay(),
            'user_id' => User::factory()->create()->id,
        ], $attrs));
    }

    public function test_password_is_hashed_on_save(): void
    {
        $post = $this->makePost(['visibility' => 'password', 'password' => 'buka123']);

        $this->assertStringStartsWith('$2y$', $post->fresh()->password);
        $this->assertTrue($post->fresh()->checkPassword('buka123'));
        $this->assertFalse($post->fresh()->checkPassword('salah'));
    }

    public function test_switching_away_from_password_clears_it(): void
    {
        $post = $this->makePost(['visibility' => 'password', 'password' => 'x']);
        $this->assertNotNull($post->fresh()->password);

        $post->update(['visibility' => 'public']);

        $this->assertNull($post->fresh()->password);
    }

    public function test_protected_post_hides_body_until_unlocked(): void
    {
        $post = $this->makePost(['visibility' => 'password', 'password' => 'buka123']);

        $res = $this->getJson("/api/v1/posts/{$post->slug}");
        $res->assertOk()
            ->assertJsonPath('data.protected', true)
            ->assertJsonPath('data.unlocked', false)
            ->assertJsonPath('data.body', null);
    }

    public function test_unlock_with_wrong_password_fails(): void
    {
        $post = $this->makePost(['visibility' => 'password', 'password' => 'buka123']);

        $this->postJson("/api/v1/posts/{$post->slug}/unlock", ['password' => 'salah'])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Kata sandi salah.');
    }

    public function test_unlock_token_reveals_body(): void
    {
        $post = $this->makePost(['visibility' => 'password', 'password' => 'buka123']);

        $token = $this->postJson("/api/v1/posts/{$post->slug}/unlock", ['password' => 'buka123'])
            ->assertOk()
            ->assertJsonPath('unlocked', true)
            ->json('token');

        $this->getJson("/api/v1/posts/{$post->slug}?unlock={$token}")
            ->assertOk()
            ->assertJsonPath('data.unlocked', true)
            ->assertJsonPath('data.body', '<p>Isi rahasia.</p>');
    }

    public function test_unlock_token_is_post_specific(): void
    {
        $a = $this->makePost(['visibility' => 'password', 'password' => 'aaa']);
        $b = $this->makePost(['visibility' => 'password', 'password' => 'bbb', 'title' => 'B']);

        $tokenA = $this->postJson("/api/v1/posts/{$a->slug}/unlock", ['password' => 'aaa'])->json('token');

        // Token untuk A tidak membuka B.
        $this->getJson("/api/v1/posts/{$b->slug}?unlock={$tokenA}")
            ->assertOk()
            ->assertJsonPath('data.unlocked', false)
            ->assertJsonPath('data.body', null);
    }

    public function test_private_post_returns_404_and_is_excluded_from_lists(): void
    {
        $private = $this->makePost(['visibility' => 'private', 'title' => 'Privat']);
        $this->makePost(['title' => 'Publik']);

        $this->getJson("/api/v1/posts/{$private->slug}")->assertNotFound();

        $res = $this->getJson('/api/v1/posts?per_page=50');
        $slugs = collect($res->json('data'))->pluck('slug');
        $this->assertFalse($slugs->contains($private->slug));
        $this->assertTrue($slugs->contains('publik'));
    }

    public function test_unlock_rejected_for_non_protected_post(): void
    {
        $post = $this->makePost();

        $this->postJson("/api/v1/posts/{$post->slug}/unlock", ['password' => 'x'])
            ->assertStatus(422);
    }
}
