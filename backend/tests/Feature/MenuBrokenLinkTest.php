<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class MenuBrokenLinkTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('public-api');
    }

    private function menu(): Menu
    {
        return Menu::create(['key' => 'utama', 'label' => 'Menu Utama']);
    }

    public function test_item_pointing_at_deleted_page_is_dropped(): void
    {
        $menu = $this->menu();
        $page = Page::create(['title' => 'Sejarah', 'slug' => 'sejarah', 'is_published' => true]);

        MenuItem::create([
            'menu_id' => $menu->id, 'label' => 'Sejarah', 'type' => 'page',
            'page_id' => $page->id, 'is_active' => true, 'order' => 1,
        ]);
        MenuItem::create([
            'menu_id' => $menu->id, 'label' => 'Kontak', 'type' => 'custom_url',
            'url' => '/kontak', 'is_active' => true, 'order' => 2,
        ]);

        // Sebelum dihapus: dua item tersaji.
        $this->getJson('/api/v1/menus/utama')->assertOk()->assertJsonCount(2, 'items');

        $page->forceDelete();

        // Setelah halaman hilang: item bertautan mati tidak dikirim.
        $res = $this->getJson('/api/v1/menus/utama')->assertOk();
        $res->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.label', 'Kontak');
    }

    public function test_section_without_url_is_still_served(): void
    {
        $menu = $this->menu();

        // Tipe `section` memang tanpa tautan — hanya pengelompok, harus tetap ada.
        MenuItem::create([
            'menu_id' => $menu->id, 'label' => 'Profil', 'type' => 'section',
            'is_active' => true, 'order' => 1,
        ]);

        $this->getJson('/api/v1/menus/utama')
            ->assertOk()
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.label', 'Profil')
            ->assertJsonPath('items.0.href', null);
    }

    public function test_custom_url_item_without_url_is_dropped(): void
    {
        $menu = $this->menu();

        MenuItem::create([
            'menu_id' => $menu->id, 'label' => 'Kosong', 'type' => 'custom_url',
            'url' => null, 'is_active' => true, 'order' => 1,
        ]);

        $this->getJson('/api/v1/menus/utama')
            ->assertOk()
            ->assertJsonCount(0, 'items');
    }

    public function test_broken_child_is_dropped_but_parent_survives(): void
    {
        $menu = $this->menu();
        $page = Page::create(['title' => 'Visi', 'slug' => 'visi', 'is_published' => true]);

        $parent = MenuItem::create([
            'menu_id' => $menu->id, 'label' => 'Profil', 'type' => 'section',
            'is_active' => true, 'order' => 1,
        ]);
        MenuItem::create([
            'menu_id' => $menu->id, 'parent_id' => $parent->id, 'label' => 'Visi',
            'type' => 'page', 'page_id' => $page->id, 'is_active' => true, 'order' => 1,
        ]);
        MenuItem::create([
            'menu_id' => $menu->id, 'parent_id' => $parent->id, 'label' => 'Sehat',
            'type' => 'custom_url', 'url' => '/sehat', 'is_active' => true, 'order' => 2,
        ]);

        $page->forceDelete();

        $res = $this->getJson('/api/v1/menus/utama')->assertOk();

        // Induk tetap ada, anak yang rusak hilang, anak sehat bertahan.
        $res->assertJsonCount(1, 'items')
            ->assertJsonCount(1, 'items.0.children')
            ->assertJsonPath('items.0.children.0.label', 'Sehat');
    }

    public function test_is_renderable_helper(): void
    {
        $menu = $this->menu();

        $section = MenuItem::make(['type' => 'section']);
        $this->assertTrue($section->isRenderable());

        $dead = MenuItem::make(['type' => 'custom_url', 'url' => null]);
        $this->assertFalse($dead->isRenderable());

        $live = MenuItem::make(['type' => 'custom_url', 'url' => '/ok']);
        $this->assertTrue($live->isRenderable());
    }
}
