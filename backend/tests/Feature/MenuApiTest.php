<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class MenuApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('public-api');
    }

    public function test_menu_endpoint_returns_nested_tree_with_expected_shape(): void
    {
        $menu = Menu::create(['key' => 'header', 'label' => 'Navigasi']);

        $home = MenuItem::create([
            'menu_id' => $menu->id, 'label' => 'Beranda', 'type' => 'custom_url',
            'url' => '/', 'order' => 0, 'is_active' => true,
        ]);

        $group = MenuItem::create([
            'menu_id' => $menu->id, 'label' => 'Profil', 'type' => 'section',
            'icon' => 'M1 1', 'order' => 1, 'is_active' => true,
            'feature_title' => 'PPDB', 'feature_text' => 'Daftar sekarang', 'feature_cta' => 'Buka',
        ]);
        MenuItem::create([
            'menu_id' => $menu->id, 'parent_id' => $group->id, 'label' => 'Sejarah',
            'type' => 'custom_url', 'url' => '/profil/sejarah', 'order' => 0, 'is_active' => true,
        ]);

        $response = $this->getJson('/api/v1/menus/header');

        $response->assertOk()
            ->assertJsonPath('key', 'header')
            ->assertJsonPath('items.0.label', 'Beranda')
            ->assertJsonPath('items.0.href', '/')
            ->assertJsonPath('items.0.children', [])
            ->assertJsonPath('items.1.label', 'Profil')
            ->assertJsonPath('items.1.href', null)
            ->assertJsonPath('items.1.feature.title', 'PPDB')
            ->assertJsonPath('items.1.children.0.label', 'Sejarah')
            ->assertJsonPath('items.1.children.0.href', '/profil/sejarah');

        unset($home);
    }

    public function test_menu_endpoint_excludes_inactive_items(): void
    {
        $menu = Menu::create(['key' => 'footer', 'label' => 'Footer']);
        MenuItem::create(['menu_id' => $menu->id, 'label' => 'Aktif', 'type' => 'custom_url', 'url' => '/a', 'order' => 0, 'is_active' => true]);
        MenuItem::create(['menu_id' => $menu->id, 'label' => 'Nonaktif', 'type' => 'custom_url', 'url' => '/b', 'order' => 1, 'is_active' => false]);

        $response = $this->getJson('/api/v1/menus/footer');

        $response->assertOk()->assertJsonCount(1, 'items');
        $this->assertSame('Aktif', $response->json('items.0.label'));
    }

    public function test_page_type_menu_item_resolves_to_profil_slug_url(): void
    {
        $menu = Menu::create(['key' => 'header', 'label' => 'Nav']);
        $page = Page::create(['title' => 'Visi Misi', 'slug' => 'visi-misi', 'is_published' => true]);
        MenuItem::create([
            'menu_id' => $menu->id, 'label' => 'Visi Misi', 'type' => 'page',
            'page_id' => $page->id, 'order' => 0, 'is_active' => true,
        ]);

        $this->getJson('/api/v1/menus/header')
            ->assertOk()
            ->assertJsonPath('items.0.href', '/profil/visi-misi');
    }

    public function test_post_category_and_tag_menu_items_resolve_to_live_slug_urls(): void
    {
        $menu = Menu::create(['key' => 'header', 'label' => 'Nav']);

        $post = Post::create([
            'title' => 'Artikel Menu', 'slug' => 'artikel-menu', 'body' => 'x',
            'status' => Post::STATUS_PUBLISHED, 'published_at' => now()->subDay(),
        ]);
        $cat = Category::create(['name' => 'Pengumuman', 'slug' => 'pengumuman', 'type' => 'post']);
        $tag = Tag::create(['name' => 'PPDB', 'slug' => 'ppdb']);

        MenuItem::create(['menu_id' => $menu->id, 'label' => 'Artikel', 'type' => 'post', 'post_id' => $post->id, 'order' => 0, 'is_active' => true]);
        MenuItem::create(['menu_id' => $menu->id, 'label' => 'Kategori', 'type' => 'category', 'category_id' => $cat->id, 'order' => 1, 'is_active' => true]);
        MenuItem::create(['menu_id' => $menu->id, 'label' => 'Tag', 'type' => 'tag', 'tag_id' => $tag->id, 'order' => 2, 'is_active' => true]);

        $this->getJson('/api/v1/menus/header')
            ->assertOk()
            ->assertJsonPath('items.0.href', '/berita/artikel-menu')
            ->assertJsonPath('items.1.href', '/berita/kategori/pengumuman')
            ->assertJsonPath('items.2.href', '/berita/tag/ppdb');
    }

    public function test_content_ref_menu_item_is_dropped_when_target_deleted(): void
    {
        $menu = Menu::create(['key' => 'header', 'label' => 'Nav']);
        $post = Post::create([
            'title' => 'Sementara', 'slug' => 'sementara', 'body' => 'x',
            'status' => Post::STATUS_PUBLISHED, 'published_at' => now()->subDay(),
        ]);
        MenuItem::create(['menu_id' => $menu->id, 'label' => 'Artikel', 'type' => 'post', 'post_id' => $post->id, 'order' => 0, 'is_active' => true]);

        $post->forceDelete();

        // FK nullOnDelete → post_id null → resolvedUrl() null → disaring MenuController.
        $this->getJson('/api/v1/menus/header')->assertOk()->assertJsonCount(0, 'items');
    }

    public function test_unknown_menu_key_returns_404(): void
    {
        $this->getJson('/api/v1/menus/does-not-exist')->assertNotFound();
    }
}
