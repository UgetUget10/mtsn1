<?php

namespace Tests\Feature;

use App\Filament\Resources\Posts\Pages\ListPosts;
use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * wp: "Bulk Actions → Edit" pada daftar Posts — ubah beberapa field untuk
 * banyak berita sekaligus; field yang dibiarkan kosong tidak menyentuh baris.
 */
class PostBulkEditTest extends TestCase
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

    private function mkPost(string $title, array $attrs = []): Post
    {
        return Post::create(array_merge([
            'title' => $title, 'body' => '<p>Isi.</p>',
            'status' => Post::STATUS_DRAFT,
        ], $attrs));
    }

    public function test_bulk_edit_changes_only_filled_fields(): void
    {
        $this->actingAsSuperAdmin();
        $author = User::factory()->create();
        $a = $this->mkPost('Satu');
        $b = $this->mkPost('Dua', ['status' => Post::STATUS_PUBLISHED, 'published_at' => now()->subWeek()]);

        Livewire::test(ListPosts::class)
            ->callTableBulkAction('bulkEdit', [$a, $b], data: [
                'user_id' => $author->id,
                'status' => Post::STATUS_PUBLISHED,
                'is_featured' => 1,
                // sisanya dibiarkan kosong
            ])
            ->assertHasNoTableBulkActionErrors();

        $a->refresh();
        $b->refresh();
        $this->assertSame($author->id, $a->user_id);
        $this->assertSame($author->id, $b->user_id);
        $this->assertTrue($a->is_featured);
        $this->assertSame(Post::STATUS_PUBLISHED, $a->status);
        // Terbit tanpa tanggal → di-set sekarang; yang sudah punya tak diubah.
        $this->assertNotNull($a->published_at);
        $this->assertTrue($b->published_at->lt(now()->subDay()));
    }

    public function test_bulk_edit_adds_categories_and_tags_without_detaching(): void
    {
        $this->actingAsSuperAdmin();
        $existing = Category::create(['name' => 'Lama', 'slug' => 'lama', 'type' => 'post']);
        $add = Category::create(['name' => 'Baru', 'slug' => 'baru', 'type' => 'post']);
        $tag = Tag::create(['name' => 'Penting', 'slug' => 'penting']);

        $p = $this->mkPost('Artikel');
        $p->categories()->attach($existing);

        Livewire::test(ListPosts::class)
            ->callTableBulkAction('bulkEdit', [$p], data: [
                'add_categories' => [$add->id],
                'add_tags' => [$tag->id],
            ])
            ->assertHasNoTableBulkActionErrors();

        $p->refresh();
        $this->assertEqualsCanonicalizing(
            [$existing->id, $add->id],
            $p->categories->pluck('id')->all(),
        );
        $this->assertSame([$tag->id], $p->tags->pluck('id')->all());
    }

    public function test_bulk_edit_sets_comment_status_in_meta(): void
    {
        $this->actingAsSuperAdmin();
        $p = $this->mkPost('Artikel', ['meta' => ['seo_title' => 'X']]);

        Livewire::test(ListPosts::class)
            ->callTableBulkAction('bulkEdit', [$p], data: ['comments_closed' => 1])
            ->assertHasNoTableBulkActionErrors();

        $p->refresh();
        $this->assertTrue($p->meta['comments_closed']);
        $this->assertSame('X', $p->meta['seo_title']); // meta lain tak hilang
    }

    public function test_bulk_edit_with_all_fields_empty_is_a_noop(): void
    {
        $this->actingAsSuperAdmin();
        $p = $this->mkPost('Artikel');
        $before = $p->updated_at;

        Livewire::test(ListPosts::class)
            ->callTableBulkAction('bulkEdit', [$p], data: [])
            ->assertHasNoTableBulkActionErrors();

        $this->assertEquals($before, $p->refresh()->updated_at);
    }
}
