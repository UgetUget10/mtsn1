<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * wp: RSS per arsip — /category/x/feed/, /tag/x/feed/, /author/x/feed/.
 * Di sini backend menyajikannya lewat query di /feed.
 */
class ArchiveFeedTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('public-api');
        Cache::flush();
    }

    private function publishedPost(string $title, array $attrs = []): Post
    {
        return Post::create(array_merge([
            'title' => $title, 'body' => '<p>Isi.</p>',
            'status' => Post::STATUS_PUBLISHED, 'published_at' => now()->subDay(),
        ], $attrs));
    }

    public function test_category_feed_contains_only_that_categorys_posts(): void
    {
        $cat = Category::create(['name' => 'Prestasi', 'slug' => 'prestasi', 'type' => 'post']);
        $other = Category::create(['name' => 'Umum', 'slug' => 'umum', 'type' => 'post']);

        $in = $this->publishedPost('Menang Lomba', ['category_id' => $cat->id]);
        $out = $this->publishedPost('Berita Lain', ['category_id' => $other->id]);

        $res = $this->get('/feed?category=prestasi')->assertOk();
        $res->assertHeader('Content-Type', 'application/rss+xml; charset=UTF-8');
        $res->assertSee('Menang Lomba', false);
        $res->assertDontSee('Berita Lain', false);
        $res->assertSee('/feed?category=prestasi', false); // atom:link self
        $res->assertSee('Kategori: Prestasi', false);      // channel title
    }

    public function test_category_feed_includes_child_category_posts(): void
    {
        $parent = Category::create(['name' => 'Akademik', 'slug' => 'akademik', 'type' => 'post']);
        $child = Category::create(['name' => 'Olimpiade', 'slug' => 'olimpiade', 'type' => 'post', 'parent_id' => $parent->id]);

        $this->publishedPost('Juara OSN', ['category_id' => $child->id]);

        $this->get('/feed?category=akademik')->assertOk()->assertSee('Juara OSN', false);
    }

    public function test_tag_feed_filters_by_tag(): void
    {
        $tag = Tag::create(['name' => 'PPDB', 'slug' => 'ppdb']);
        $p = $this->publishedPost('Info PPDB');
        $p->tags()->attach($tag);
        $this->publishedPost('Tak Bertag');

        $res = $this->get('/feed?tag=ppdb')->assertOk();
        $res->assertSee('Info PPDB', false);
        $res->assertDontSee('Tak Bertag', false);
    }

    public function test_author_feed_filters_by_author_slug(): void
    {
        $ani = User::factory()->create(['name' => 'Ani', 'slug' => 'ani']);
        $budi = User::factory()->create(['name' => 'Budi', 'slug' => 'budi']);

        $this->publishedPost('Tulisan Ani', ['user_id' => $ani->id]);
        $this->publishedPost('Tulisan Budi', ['user_id' => $budi->id]);

        $res = $this->get('/feed?author=ani')->assertOk();
        $res->assertSee('Tulisan Ani', false);
        $res->assertDontSee('Tulisan Budi', false);
        $res->assertSee('Penulis: Ani', false);
    }

    public function test_unknown_archive_slug_returns_404(): void
    {
        $this->get('/feed?category=tidak-ada')->assertNotFound();
        $this->get('/feed?tag=tidak-ada')->assertNotFound();
        $this->get('/feed?author=tidak-ada')->assertNotFound();
    }

    public function test_plain_feed_still_works(): void
    {
        $this->publishedPost('Berita Umum');
        $this->get('/feed')->assertOk()->assertSee('Berita Umum', false);
    }

    public function test_saving_a_post_busts_its_archive_feed_caches(): void
    {
        $cat = Category::create(['name' => 'Kilat', 'slug' => 'kilat', 'type' => 'post']);
        $p = $this->publishedPost('Versi Satu', ['category_id' => $cat->id]);

        $this->get('/feed?category=kilat')->assertOk()->assertSee('Versi Satu', false);

        $p->update(['title' => 'Versi Dua']);

        $this->get('/feed?category=kilat')
            ->assertOk()
            ->assertSee('Versi Dua', false)
            ->assertDontSee('Versi Satu', false);
    }
}
