<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * wp: Settings → Reading → "For each post in a feed, include: Full text / Summary".
 * Sebelumnya feed selalu memakai excerpt, tanpa opsi.
 */
class RssFeedContentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('public-api');
        Cache::flush();
    }

    private function seedPost(string $title = 'Berita RSS'): Post
    {
        return Post::create([
            'title' => $title,
            'excerpt' => 'Ringkasan pendek untuk feed.',
            'body' => '<p>Paragraf isi lengkap dengan penanda unik-XYZ.</p>',
            'status' => Post::STATUS_PUBLISHED,
            'published_at' => now()->subDay(),
            'user_id' => User::factory()->create()->id,
        ]);
    }

    public function test_default_feed_uses_summary_only(): void
    {
        $this->seedPost();

        $this->get('/feed')
            ->assertOk()
            ->assertSee('Ringkasan pendek untuk feed.', false)
            ->assertDontSee('content:encoded', false)
            ->assertDontSee('unik-XYZ', false);
    }

    public function test_full_text_mode_emits_content_encoded(): void
    {
        $this->seedPost();
        Setting::create(['key' => 'rss_content_mode', 'value' => 'lengkap', 'group' => 'general']);
        Cache::flush();

        $this->get('/feed')
            ->assertOk()
            ->assertSee('<content:encoded>', false)
            ->assertSee('unik-XYZ', false)
            ->assertSee('xmlns:content', false);
    }

    public function test_full_text_body_strips_more_tag(): void
    {
        $post = $this->seedPost();
        $post->forceFill(['body' => '<p>Teaser.</p><!--more--><p>Lanjutan unik-XYZ.</p>'])->saveQuietly();
        Setting::create(['key' => 'rss_content_mode', 'value' => 'lengkap', 'group' => 'general']);
        Cache::flush();

        $body = $this->get('/feed')->assertOk()->getContent();

        $this->assertStringNotContainsString('<!--more-->', $body);
        $this->assertStringContainsString('Lanjutan unik-XYZ.', $body);
    }

    public function test_posts_per_rss_limits_items(): void
    {
        foreach (range(1, 5) as $i) {
            $this->seedPost("Berita {$i}");
        }
        Setting::create(['key' => 'posts_per_rss', 'value' => '2', 'group' => 'general']);
        Cache::flush();

        $body = $this->get('/feed')->assertOk()->getContent();

        $this->assertSame(2, substr_count($body, '<item>'));
    }

    public function test_description_falls_back_to_auto_excerpt_when_excerpt_blank(): void
    {
        $post = $this->seedPost();
        $post->forceFill([
            'excerpt' => null,
            'body' => '<p>'.str_repeat('kata ', 30).'penanda-fallback.</p>',
        ])->saveQuietly();
        Cache::flush();

        // <description> tidak boleh kosong — dipotong otomatis dari body.
        $this->get('/feed')
            ->assertOk()
            ->assertSee('kata kata kata', false);
    }
}
