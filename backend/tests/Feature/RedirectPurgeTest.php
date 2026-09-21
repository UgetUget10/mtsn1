<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Page;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Perubahan slug konten mencatat baris redirect 301 (wp: _wp_old_slug). Middleware
 * frontend (proxy.ts) yang membaca /resolve lalu memancarkan 308; baris redirect
 * juga memicu purge tag cache "redirects" di produksi (Redirect::booted, dilewati
 * saat runningInConsole sehingga tidak diuji di sini).
 */
class RedirectPurgeTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_slug_change_records_berita_redirect(): void
    {
        $post = Post::create([
            'title' => 'Judul Awal',
            'body' => 'isi',
            'status' => Post::STATUS_PUBLISHED,
            'published_at' => now()->subDay(),
            'user_id' => User::factory()->create()->id,
        ]);

        $post->update(['slug' => 'judul-baru']);

        $this->assertDatabaseHas('redirects', [
            'from_path' => '/berita/judul-awal',
            'to_path' => '/berita/judul-baru',
            'status' => 301,
        ]);
    }

    public function test_category_slug_change_records_kategori_redirect(): void
    {
        $cat = Category::create(['name' => 'Awal', 'slug' => 'kat-awal', 'type' => 'post']);

        $cat->update(['slug' => 'kat-baru']);

        $this->assertDatabaseHas('redirects', [
            'from_path' => '/berita/kategori/kat-awal',
            'to_path' => '/berita/kategori/kat-baru',
            'status' => 301,
        ]);
    }

    public function test_tag_slug_change_records_tag_redirect(): void
    {
        $tag = Tag::create(['name' => 'Awal', 'slug' => 'tag-awal']);

        $tag->update(['slug' => 'tag-baru']);

        $this->assertDatabaseHas('redirects', [
            'from_path' => '/berita/tag/tag-awal',
            'to_path' => '/berita/tag/tag-baru',
            'status' => 301,
        ]);
    }

    public function test_page_slug_change_records_profil_redirect(): void
    {
        $page = Page::create(['title' => 'Awal', 'slug' => 'pg-awal', 'is_published' => true]);

        $page->update(['slug' => 'pg-baru']);

        $this->assertDatabaseHas('redirects', [
            'from_path' => '/profil/pg-awal',
            'to_path' => '/profil/pg-baru',
            'status' => 301,
        ]);
    }

    public function test_resolve_endpoint_serves_recorded_redirect(): void
    {
        $cat = Category::create(['name' => 'X', 'slug' => 'x-awal', 'type' => 'post']);
        $cat->update(['slug' => 'x-baru']);

        $this->getJson('/api/v1/resolve?path=/berita/kategori/x-awal')
            ->assertOk()
            ->assertJsonPath('to', '/berita/kategori/x-baru')
            ->assertJsonPath('status', 301);
    }
}
