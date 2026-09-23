<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PageCanvasControllerTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsSuperAdmin(): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(RoleSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $this->actingAs($user);

        return $user;
    }

    public function test_update_then_publish_copies_tree_into_flat_blocks(): void
    {
        $this->actingAsSuperAdmin();

        $page = Page::create(['title' => 'Kontak', 'slug' => 'kontak', 'is_published' => true, 'blocks' => []]);

        $tree = [[
            'id' => 'sec_1',
            'type' => 'section',
            'children' => [[
                'id' => 'col_1',
                'type' => 'column',
                'style' => ['base' => ['width' => 12]],
                'children' => [
                    ['id' => 'wid_1', 'type' => 'quote', 'data' => ['text' => 'Halo', 'attribution' => null]],
                ],
            ]],
        ]];

        $this->putJson("/admin/api/pages/{$page->slug}/tree", ['tree' => $tree])->assertOk();

        $page->refresh();
        $this->assertNotEmpty($page->blocks_draft);
        $this->assertEmpty($page->blocks); // publish belum ditekan — blocks published belum berubah

        $this->postJson("/admin/api/pages/{$page->slug}/publish")->assertOk();

        $page->refresh();
        $this->assertCount(1, $page->blocks);
        $this->assertSame('quote', $page->blocks[0]['type']);
        $this->assertSame('Halo', $page->blocks[0]['data']['text']);
        // Draf tetap ada (struktur section/kolom tidak hilang) setelah publish.
        $this->assertNotEmpty($page->blocks_draft);
    }

    public function test_discard_draft_clears_blocks_draft(): void
    {
        $this->actingAsSuperAdmin();

        $page = Page::create([
            'title' => 'Kontak',
            'slug' => 'kontak',
            'is_published' => true,
            'blocks' => [],
            'blocks_draft' => ['schema' => 2, 'tree' => [['id' => 'sec_1', 'type' => 'section', 'children' => []]]],
        ]);

        $this->deleteJson("/admin/api/pages/{$page->slug}/draft")->assertOk();

        $page->refresh();
        $this->assertNull($page->blocks_draft);
    }

    public function test_update_sanitizes_style_through_style_resolver(): void
    {
        $this->actingAsSuperAdmin();

        $page = Page::create(['title' => 'Kontak', 'slug' => 'kontak', 'is_published' => true, 'blocks' => []]);

        $tree = [[
            'id' => 'sec_1',
            'type' => 'section',
            // fontSize bukan kosakata valid (lihat StyleResolver::sanitizeProperties)
            // — harus dibuang; paddingY & background valid — harus tetap ada.
            'style' => ['base' => ['paddingY' => 'lg', 'background' => 'brand-light', 'fontSize' => '999px']],
            'children' => [],
        ]];

        $this->putJson("/admin/api/pages/{$page->slug}/tree", ['tree' => $tree])->assertOk();

        $page->refresh();
        $sectionStyle = $page->blocks_draft['tree'][0]['style'];
        $this->assertSame(['paddingY' => 'lg', 'background' => 'brand-light'], $sectionStyle['base']);
        $this->assertArrayNotHasKey('fontSize', $sectionStyle['base']);
    }

    public function test_non_editor_role_cannot_access_canvas_api(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(RoleSeeder::class);
        $user = User::factory()->create();
        $this->actingAs($user); // no role assigned — no Update:Page permission

        $page = Page::create(['title' => 'Kontak', 'slug' => 'kontak', 'is_published' => true, 'blocks' => []]);

        $this->getJson("/admin/api/pages/{$page->slug}/tree")->assertForbidden();
        $this->putJson("/admin/api/pages/{$page->slug}/tree", ['tree' => []])->assertForbidden();
        $this->postJson("/admin/api/pages/{$page->slug}/publish")->assertForbidden();
        $this->deleteJson("/admin/api/pages/{$page->slug}/draft")->assertForbidden();
    }
}
