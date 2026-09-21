<?php

namespace Tests\Feature;

use App\Filament\Resources\Pages\Pages\ListPages;
use App\Filament\Resources\Posts\Pages\ListPosts;
use App\Models\Page;
use App\Models\Post;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class DuplicateActionTest extends TestCase
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

    public function test_duplicate_action_creates_a_draft_copy_from_posts_table(): void
    {
        $user = $this->actingAsSuperAdmin();

        $post = Post::create([
            'title' => 'Berita Panel',
            'body' => '<p>Isi.</p>',
            'status' => Post::STATUS_PUBLISHED,
            'published_at' => now()->subDay(),
            'user_id' => $user->id,
        ]);

        Livewire::test(ListPosts::class)
            ->callTableAction('duplicate', $post)
            ->assertHasNoTableActionErrors();

        $this->assertSame(2, Post::count());
        $copy = Post::where('id', '!=', $post->id)->first();
        $this->assertSame(Post::STATUS_DRAFT, $copy->status);
        $this->assertStringEndsWith('(salinan)', $copy->title);
    }

    public function test_duplicate_action_creates_an_unpublished_copy_from_pages_table(): void
    {
        $this->actingAsSuperAdmin();

        $page = Page::create([
            'title' => 'Halaman Panel',
            'slug' => 'halaman-panel',
            'is_published' => true,
        ]);

        Livewire::test(ListPages::class)
            ->callTableAction('duplicate', $page)
            ->assertHasNoTableActionErrors();

        $copy = Page::where('id', '!=', $page->id)->first();
        $this->assertNotNull($copy);
        $this->assertFalse((bool) $copy->is_published);
        $this->assertStringEndsWith('(salinan)', $copy->title);
    }
}
