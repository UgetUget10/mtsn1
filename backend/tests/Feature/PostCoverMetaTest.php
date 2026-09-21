<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Editor sudah bisa mengisi Caption & Credit di entri Pustaka Media
 * (MediaForm), tapi sebelumnya hanya `cover_alt` yang sampai ke frontend.
 * Test ini menjaga caption/credit ikut terkirim di API berita.
 */
class PostCoverMetaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('public-api');
        Storage::fake('public');
    }

    /** PNG 2×2 valid — cukup untuk konversi GD di test. */
    private function pngBytes(): string
    {
        $img = imagecreatetruecolor(2, 2);
        ob_start();
        imagepng($img);
        $bytes = ob_get_clean();
        imagedestroy($img);

        return $bytes;
    }

    private function publishedPost(): Post
    {
        return Post::create([
            'title' => 'Berita Sampul',
            'body' => '<p>Isi.</p>',
            'status' => Post::STATUS_PUBLISHED,
            'published_at' => now()->subDay(),
            'user_id' => User::factory()->create()->id,
        ]);
    }

    public function test_cover_caption_and_credit_reach_the_api(): void
    {
        $post = $this->publishedPost();

        $post->addMediaFromString($this->pngBytes())
            ->usingFileName('c.png')
            ->withCustomProperties([
                'alt' => 'Upacara bendera',
                'caption' => 'Peserta didik mengikuti upacara Senin pagi',
                'credit' => 'Humas MTsN 1',
            ])
            ->toMediaCollection('cover');

        $this->getJson('/api/v1/posts/'.$post->slug)
            ->assertOk()
            ->assertJsonPath('data.cover_alt', 'Upacara bendera')
            ->assertJsonPath('data.cover_caption', 'Peserta didik mengikuti upacara Senin pagi')
            ->assertJsonPath('data.cover_credit', 'Humas MTsN 1');
    }

    public function test_missing_media_yields_null_caption_and_credit(): void
    {
        $post = $this->publishedPost();

        $this->getJson('/api/v1/posts/'.$post->slug)
            ->assertOk()
            ->assertJsonPath('data.cover_caption', null)
            ->assertJsonPath('data.cover_credit', null);
    }

    public function test_media_without_caption_yields_null_but_keeps_alt(): void
    {
        $post = $this->publishedPost();

        $post->addMediaFromString($this->pngBytes())
            ->usingFileName('c.png')
            ->withCustomProperties(['alt' => 'Hanya alt'])
            ->toMediaCollection('cover');

        $this->getJson('/api/v1/posts/'.$post->slug)
            ->assertOk()
            ->assertJsonPath('data.cover_alt', 'Hanya alt')
            ->assertJsonPath('data.cover_caption', null)
            ->assertJsonPath('data.cover_credit', null);
    }
}
