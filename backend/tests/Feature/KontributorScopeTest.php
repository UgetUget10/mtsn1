<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class KontributorScopeTest extends TestCase
{
    use RefreshDatabase;

    private function seedRoles(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(RoleSeeder::class);
    }

    public function test_kontributor_can_edit_own_post_but_not_others(): void
    {
        $this->seedRoles();

        $kontributor = User::factory()->create();
        $kontributor->assignRole('kontributor');

        $other = User::factory()->create();

        $ownPost = Post::create(['title' => 'Milik Sendiri', 'slug' => 'milik-sendiri', 'user_id' => $kontributor->id, 'status' => 'draft']);
        $othersPost = Post::create(['title' => 'Milik Orang', 'slug' => 'milik-orang', 'user_id' => $other->id, 'status' => 'draft']);

        $this->assertTrue($kontributor->can('update', $ownPost));
        $this->assertFalse($kontributor->can('update', $othersPost));
    }

    public function test_editor_can_edit_any_post(): void
    {
        $this->seedRoles();

        $editor = User::factory()->create();
        $editor->assignRole('editor');

        $other = User::factory()->create();
        $post = Post::create(['title' => 'Berita', 'slug' => 'berita', 'user_id' => $other->id, 'status' => 'draft']);

        $this->assertTrue($editor->can('update', $post));
    }
}
