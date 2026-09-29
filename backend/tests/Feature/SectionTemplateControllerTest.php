<?php

namespace Tests\Feature;

use App\Models\SectionTemplate;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SectionTemplateControllerTest extends TestCase
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

    private function sampleSection(): array
    {
        return [
            'id' => 'sec_orig1',
            'type' => 'section',
            'style' => ['base' => ['paddingY' => 'lg', 'fontSize' => '999px']], // fontSize harus dibuang
            'children' => [[
                'id' => 'col_orig1',
                'type' => 'column',
                'style' => ['base' => ['width' => 12]],
                'children' => [
                    ['id' => 'wid_orig1', 'type' => 'quote', 'data' => ['text' => 'Halo', 'attribution' => null]],
                ],
            ]],
        ];
    }

    public function test_store_saves_section_and_sanitizes_style(): void
    {
        $this->actingAsSuperAdmin();

        $response = $this->postJson('/admin/api/section-templates', [
            'name' => 'Hero Sambutan',
            'section' => $this->sampleSection(),
        ])->assertOk();

        $template = SectionTemplate::where('slug', $response->json('slug'))->firstOrFail();

        $this->assertSame('Hero Sambutan', $template->name);
        $this->assertSame(['paddingY' => 'lg'], $template->section['style']['base']);
        $this->assertArrayNotHasKey('fontSize', $template->section['style']['base']);
    }

    public function test_index_lists_templates_with_widget_count(): void
    {
        $this->actingAsSuperAdmin();
        SectionTemplate::create(['name' => 'A', 'section' => $this->sampleSection()]);

        $response = $this->getJson('/admin/api/section-templates')->assertOk();

        $this->assertCount(1, $response->json());
        $this->assertSame(1, $response->json('0.widgetCount'));
    }

    public function test_show_regenerates_all_node_ids(): void
    {
        $this->actingAsSuperAdmin();
        $template = SectionTemplate::create(['name' => 'A', 'section' => $this->sampleSection()]);

        $response = $this->getJson("/admin/api/section-templates/{$template->slug}")->assertOk();

        $section = $response->json('section');
        $this->assertNotSame('sec_orig1', $section['id']);
        $this->assertNotSame('col_orig1', $section['children'][0]['id']);
        $this->assertNotSame('wid_orig1', $section['children'][0]['children'][0]['id']);
        // Isi (data) tetap sama — hanya id yang berubah.
        $this->assertSame('Halo', $section['children'][0]['children'][0]['data']['text']);
    }

    public function test_show_called_twice_produces_different_ids_each_time(): void
    {
        $this->actingAsSuperAdmin();
        $template = SectionTemplate::create(['name' => 'A', 'section' => $this->sampleSection()]);

        $first = $this->getJson("/admin/api/section-templates/{$template->slug}")->json('section');
        $second = $this->getJson("/admin/api/section-templates/{$template->slug}")->json('section');

        $this->assertNotSame($first['id'], $second['id']);
    }

    public function test_non_editor_role_cannot_access_section_template_api(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(RoleSeeder::class);
        $user = User::factory()->create();
        $this->actingAs($user);

        $template = SectionTemplate::create(['name' => 'A', 'section' => $this->sampleSection()]);

        $this->getJson('/admin/api/section-templates')->assertForbidden();
        $this->postJson('/admin/api/section-templates', ['name' => 'X', 'section' => $this->sampleSection()])->assertForbidden();
        $this->getJson("/admin/api/section-templates/{$template->slug}")->assertForbidden();
    }
}
