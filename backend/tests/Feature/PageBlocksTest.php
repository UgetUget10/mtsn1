<?php

namespace Tests\Feature;

use App\Filament\Resources\Pages\Pages\CreatePage;
use App\Models\Page;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PageBlocksTest extends TestCase
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

    public function test_card_grid_block_persists_through_filament_builder(): void
    {
        $this->actingAsSuperAdmin();

        Livewire::test(CreatePage::class)
            ->fillForm([
                'title' => ['id' => 'Kurikulum'],
                'slug' => 'kurikulum',
                'is_published' => true,
                'order' => 0,
                'blocks' => [
                    'block-1' => [
                        'type' => 'card_grid',
                        'data' => [
                            'heading' => 'Kurikulum',
                            'columns' => 2,
                            'cards' => [
                                ['title' => 'Kurikulum Merdeka', 'description' => 'Diterapkan bertahap.', 'icon' => null, 'image' => null, 'href' => null],
                            ],
                        ],
                    ],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $page = Page::where('slug', 'kurikulum')->firstOrFail();

        $this->assertNotEmpty($page->blocks);
        $this->assertSame('card_grid', $page->blocks[0]['type']);
        $this->assertSame('Kurikulum Merdeka', $page->blocks[0]['data']['cards'][0]['title']);
    }

    public function test_legacy_body_is_wrapped_as_rich_text_block_when_blocks_empty(): void
    {
        $page = Page::create([
            'title' => 'Halaman Lama',
            'slug' => 'halaman-lama',
            'body' => '<p>Konten lama.</p>',
            'is_published' => true,
        ]);

        $blocks = $page->visibleBlocks();

        $this->assertCount(1, $blocks);
        $this->assertSame('rich_text', $blocks[0]['type']);
        $this->assertSame('<p>Konten lama.</p>', $blocks[0]['data']['body']);
    }

    public function test_invisible_blocks_are_excluded(): void
    {
        $page = Page::create([
            'title' => 'Halaman Blok',
            'slug' => 'halaman-blok',
            'is_published' => true,
            'blocks' => [
                ['type' => 'rich_text', 'is_visible' => true, 'data' => ['heading' => null, 'body' => 'Tampil']],
                ['type' => 'rich_text', 'is_visible' => false, 'data' => ['heading' => null, 'body' => 'Sembunyi']],
            ],
        ]);

        $blocks = $page->visibleBlocks();

        $this->assertCount(1, $blocks);
        $this->assertSame('Tampil', $blocks[0]['data']['body']);
    }
}
