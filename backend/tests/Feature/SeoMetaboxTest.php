<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SeoMetaboxTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('public-api');
    }

    private function mkPost(array $meta = []): Post
    {
        return Post::create([
            'title' => 'Judul Asli',
            'excerpt' => 'Ringkasan asli.',
            'body' => '<p>Isi.</p>',
            'status' => Post::STATUS_PUBLISHED,
            'published_at' => now()->subDay(),
            'user_id' => User::factory()->create()->id,
            'meta' => $meta,
        ]);
    }

    public function test_post_seo_overrides_fall_back_to_content(): void
    {
        $post = $this->mkPost();

        $this->getJson("/api/v1/posts/{$post->slug}")
            ->assertOk()
            ->assertJsonPath('data.seo.title', 'Judul Asli')
            ->assertJsonPath('data.seo.description', 'Ringkasan asli.')
            ->assertJsonPath('data.seo.noindex', false);
    }

    public function test_post_seo_overrides_are_used_when_set(): void
    {
        $post = $this->mkPost([
            'seo_title' => 'Judul SEO Khusus',
            'seo_description' => 'Deskripsi SEO khusus.',
            'canonical' => 'https://contoh.test/asli',
            'noindex' => true,
        ]);

        $this->getJson("/api/v1/posts/{$post->slug}")
            ->assertOk()
            ->assertJsonPath('data.seo.title', 'Judul SEO Khusus')
            ->assertJsonPath('data.seo.description', 'Deskripsi SEO khusus.')
            ->assertJsonPath('data.seo.canonical', 'https://contoh.test/asli')
            ->assertJsonPath('data.seo.noindex', true);
    }

    public function test_post_og_image_relative_path_becomes_absolute_url(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('seo/share.jpg', 'x');

        $post = $this->mkPost(['og_image' => 'seo/share.jpg']);

        $og = $this->getJson("/api/v1/posts/{$post->slug}")->json('data.seo.og_image');

        $this->assertStringStartsWith('http', $og);
        $this->assertStringContainsString('storage/seo/share.jpg', $og);
    }

    public function test_post_og_image_external_url_passes_through(): void
    {
        $post = $this->mkPost(['og_image' => 'https://cdn.test/gambar.jpg']);

        $this->getJson("/api/v1/posts/{$post->slug}")
            ->assertJsonPath('data.seo.og_image', 'https://cdn.test/gambar.jpg');
    }

    public function test_page_exposes_seo_block_from_meta(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('seo/halaman.jpg', 'x');

        $page = Page::create([
            'title' => 'Sejarah',
            'slug' => 'sejarah',
            'is_published' => true,
            'meta' => [
                'seo_title' => 'Sejarah Madrasah',
                'canonical' => 'https://contoh.test/sejarah',
                'noindex' => true,
                'og_image' => 'seo/halaman.jpg',
            ],
        ]);

        $res = $this->getJson("/api/v1/pages/{$page->slug}")->assertOk();

        $res->assertJsonPath('data.seo.title', 'Sejarah Madrasah')
            ->assertJsonPath('data.seo.canonical', 'https://contoh.test/sejarah')
            ->assertJsonPath('data.seo.noindex', true);

        $this->assertStringContainsString(
            'storage/seo/halaman.jpg',
            $res->json('data.seo.og_image'),
        );
    }
}
