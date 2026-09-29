<?php

namespace Tests\Feature;

use App\Models\GlobalTemplate;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class GlobalTemplateControllerTest extends TestCase
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

    private function sampleTree(): array
    {
        return [
            [
                'id' => 'sec1',
                'type' => 'section',
                'style' => [],
                'children' => [[
                    'id' => 'col1',
                    'type' => 'column',
                    'style' => [],
                    'children' => [
                        ['id' => 'wid1', 'type' => 'quote', 'data' => ['text' => 'Selamat datang', 'attribution' => null]],
                    ],
                ]],
            ],
        ];
    }

    public function test_update_saves_draft_without_touching_published_tree(): void
    {
        $this->actingAsSuperAdmin();
        $template = GlobalTemplate::create(['name' => 'Header Utama', 'slot' => GlobalTemplate::SLOT_HEADER]);

        $this->putJson("/admin/api/global-templates/{$template->id}/tree", ['tree' => $this->sampleTree()])
            ->assertOk();

        $template->refresh();
        $this->assertNotEmpty($template->tree_draft);
        $this->assertNull($template->tree);
    }

    public function test_publish_copies_draft_and_activates_slot_exclusively(): void
    {
        $this->actingAsSuperAdmin();
        $old = GlobalTemplate::create([
            'name' => 'Header Lama', 'slot' => GlobalTemplate::SLOT_HEADER, 'is_active' => true,
        ]);
        $new = GlobalTemplate::create(['name' => 'Header Baru', 'slot' => GlobalTemplate::SLOT_HEADER]);

        $this->putJson("/admin/api/global-templates/{$new->id}/tree", ['tree' => $this->sampleTree()])->assertOk();
        $this->postJson("/admin/api/global-templates/{$new->id}/publish")->assertOk();

        $this->assertTrue($new->fresh()->is_active);
        $this->assertFalse($old->fresh()->is_active);
        $this->assertNotEmpty($new->fresh()->tree);
    }

    public function test_discard_draft_clears_draft_only(): void
    {
        $this->actingAsSuperAdmin();
        $template = GlobalTemplate::create(['name' => 'Footer', 'slot' => GlobalTemplate::SLOT_FOOTER]);
        $this->putJson("/admin/api/global-templates/{$template->id}/tree", ['tree' => $this->sampleTree()])->assertOk();

        $this->deleteJson("/admin/api/global-templates/{$template->id}/draft")->assertOk();

        $this->assertNull($template->fresh()->tree_draft);
    }

    public function test_public_endpoint_returns_active_template_for_slot(): void
    {
        GlobalTemplate::create([
            'name' => 'Footer Aktif',
            'slot' => GlobalTemplate::SLOT_FOOTER,
            'is_active' => true,
            'tree' => ['schema' => 2, 'tree' => $this->sampleTree()],
        ]);
        GlobalTemplate::create([
            'name' => 'Footer Draf',
            'slot' => GlobalTemplate::SLOT_FOOTER,
            'is_active' => false,
            'tree' => ['schema' => 2, 'tree' => []],
        ]);

        $response = $this->getJson('/api/v1/global-templates/footer')->assertOk();

        $this->assertTrue($response->json('active'));
        $this->assertSame('quote', $response->json('tree.tree.0.children.0.children.0.type'));
    }

    public function test_public_endpoint_returns_empty_tree_when_slot_has_no_active_design(): void
    {
        $response = $this->getJson('/api/v1/global-templates/header')->assertOk();

        $this->assertFalse($response->json('active'));
        $this->assertSame([], $response->json('tree.tree'));
    }

    public function test_non_editor_role_cannot_access_global_template_canvas_api(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(RoleSeeder::class);
        $user = User::factory()->create();
        $this->actingAs($user);

        $template = GlobalTemplate::create(['name' => 'Header', 'slot' => GlobalTemplate::SLOT_HEADER]);

        $this->getJson("/admin/api/global-templates/{$template->id}/tree")->assertForbidden();
        $this->putJson("/admin/api/global-templates/{$template->id}/tree", ['tree' => []])->assertForbidden();
        $this->postJson("/admin/api/global-templates/{$template->id}/publish")->assertForbidden();
    }
}
