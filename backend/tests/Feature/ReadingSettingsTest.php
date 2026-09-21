<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Setting;
use App\Models\User;
use App\Support\Revalidation\RevalidationTargets;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class ReadingSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('public-api');
        Cache::forget('settings.all');
    }

    private function seedPosts(int $n): void
    {
        $author = User::factory()->create();
        for ($i = 1; $i <= $n; $i++) {
            Post::create([
                'title' => "Berita {$i}",
                'body' => '<p>Isi.</p>',
                'status' => Post::STATUS_PUBLISHED,
                'published_at' => now()->subDays($n - $i + 1),
                'user_id' => $author->id,
            ]);
        }
    }

    public function test_defaults_to_twelve_when_setting_absent(): void
    {
        $this->seedPosts(15);

        $this->getJson('/api/v1/posts')
            ->assertOk()
            ->assertJsonCount(12, 'data');
    }

    public function test_posts_per_page_setting_is_honoured(): void
    {
        $this->seedPosts(15);
        Setting::create(['key' => 'posts_per_page', 'value' => '5']);
        Cache::forget('settings.all');

        $this->getJson('/api/v1/posts')
            ->assertOk()
            ->assertJsonCount(5, 'data');
    }

    public function test_explicit_per_page_query_overrides_the_setting(): void
    {
        $this->seedPosts(15);
        Setting::create(['key' => 'posts_per_page', 'value' => '5']);
        Cache::forget('settings.all');

        $this->getJson('/api/v1/posts?per_page=3')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_per_page_is_clamped_to_one_hundred(): void
    {
        $this->seedPosts(3);

        // Permintaan berlebihan tidak boleh memaksa query raksasa.
        $this->getJson('/api/v1/posts?per_page=99999')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 100);

        $this->getJson('/api/v1/posts?per_page=0')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 1);
    }

    public function test_sitemap_sized_request_is_not_truncated(): void
    {
        // sitemap.ts meminta per_page=100 — batas atas harus mengizinkannya.
        $this->seedPosts(3);

        $this->getJson('/api/v1/posts?per_page=100')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 100);
    }

    public function test_setting_change_purges_posts_tag(): void
    {
        $setting = Setting::create(['key' => 'posts_per_page', 'value' => '5']);

        $targets = RevalidationTargets::for($setting);

        // Tanpa tag `posts`, arsip berita tak pernah menyegarkan jumlah itemnya.
        $this->assertContains('posts', $targets['tags']);
        $this->assertContains('settings', $targets['tags']);
    }
}
