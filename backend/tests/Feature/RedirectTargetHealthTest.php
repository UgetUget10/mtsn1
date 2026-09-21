<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Page;
use App\Models\Post;
use App\Models\Redirect;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RedirectTargetHealthTest extends TestCase
{
    use RefreshDatabase;

    public function test_detects_live_post_target(): void
    {
        Post::create([
            'title' => 'Hidup',
            'slug' => 'hidup',
            'status' => Post::STATUS_PUBLISHED,
            'published_at' => now()->subDay(),
            'user_id' => User::factory()->create()->id,
        ]);

        $r = Redirect::create(['from_path' => '/berita/lama', 'to_path' => '/berita/hidup', 'status' => 301]);

        $this->assertTrue($r->targetExists());
    }

    public function test_detects_dead_post_target(): void
    {
        $r = Redirect::create(['from_path' => '/berita/lama', 'to_path' => '/berita/tidak-ada', 'status' => 301]);

        $this->assertFalse($r->targetExists());
    }

    public function test_detects_page_category_and_tag_targets(): void
    {
        Page::create(['title' => 'Sejarah', 'slug' => 'sejarah', 'is_published' => true]);
        Category::create(['name' => 'Berita', 'slug' => 'kabar', 'type' => 'post']);
        Tag::create(['name' => 'Prestasi', 'slug' => 'prestasi']);

        $this->assertTrue(
            Redirect::create(['from_path' => '/a', 'to_path' => '/profil/sejarah', 'status' => 301])->targetExists(),
        );
        $this->assertTrue(
            Redirect::create(['from_path' => '/b', 'to_path' => '/berita/kategori/kabar', 'status' => 301])->targetExists(),
        );
        $this->assertTrue(
            Redirect::create(['from_path' => '/c', 'to_path' => '/berita/tag/prestasi', 'status' => 301])->targetExists(),
        );

        $this->assertFalse(
            Redirect::create(['from_path' => '/d', 'to_path' => '/profil/hantu', 'status' => 301])->targetExists(),
        );
        $this->assertFalse(
            Redirect::create(['from_path' => '/e', 'to_path' => '/berita/kategori/hantu', 'status' => 301])->targetExists(),
        );
    }

    public function test_unknown_pattern_is_not_flagged(): void
    {
        $r = Redirect::create(['from_path' => '/x', 'to_path' => '/kontak', 'status' => 301]);

        $this->assertTrue($r->targetExists());
    }
}
