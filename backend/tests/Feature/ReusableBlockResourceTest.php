<?php

namespace Tests\Feature;

use App\Filament\Resources\ReusableBlocks\Pages\CreateReusableBlock;
use App\Filament\Resources\ReusableBlocks\Pages\ListReusableBlocks;
use App\Models\Page;
use App\Models\ReusableBlock;
use App\Models\User;
use App\Support\Blocks\BlockTypes;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Blok Dipakai Ulang = Synced Pattern WordPress. Model, tabel, dan renderer
 * sudah lama ada, tapi tanpa UI admin tabelnya selalu kosong sehingga blok
 * `reusable` di halaman selalu render kosong.
 */
class ReusableBlockResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('public-api');
    }

    private function actingAsSuperAdmin(): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(RoleSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $this->actingAs($user);

        return $user;
    }

    private function richTextContent(string $body): array
    {
        return [[
            'type' => BlockTypes::RICH_TEXT,
            'data' => ['heading' => null, 'body' => $body],
        ]];
    }

    public function test_admin_can_create_a_reusable_block_and_slug_is_generated(): void
    {
        $this->actingAsSuperAdmin();

        Livewire::test(CreateReusableBlock::class)
            ->fillForm([
                'name' => 'Info Pendaftaran',
                'is_active' => true,
                'content' => $this->richTextContent('<p>Halo.</p>'),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $block = ReusableBlock::first();

        $this->assertNotNull($block);
        $this->assertSame('Info Pendaftaran', $block->name);
        $this->assertSame('info-pendaftaran', $block->slug);
        $this->assertTrue($block->is_active);
    }

    public function test_listing_page_renders(): void
    {
        $this->actingAsSuperAdmin();
        ReusableBlock::create([
            'name' => 'Blok A',
            'content' => $this->richTextContent('<p>A.</p>'),
        ]);

        Livewire::test(ListReusableBlocks::class)->assertOk();
    }

    /** Integrasi inti: isi blok harus ter-inline ke payload halaman. */
    public function test_page_inlines_the_reusable_block_content(): void
    {
        $block = ReusableBlock::create([
            'name' => 'Kontak Singkat',
            'is_active' => true,
            'content' => $this->richTextContent('<p>Telepon 0341-000.</p>'),
        ]);

        $page = Page::create([
            'title' => 'Bantuan',
            'slug' => 'bantuan',
            'is_published' => true,
            'blocks' => [[
                'type' => BlockTypes::REUSABLE,
                'data' => ['slug' => $block->slug],
            ]],
        ]);

        $res = $this->getJson("/api/v1/pages/{$page->slug}")->assertOk();

        // Blok `reusable` diganti isinya, bukan diteruskan mentah.
        $res->assertJsonPath('data.blocks.0.type', BlockTypes::RICH_TEXT);
        $this->assertStringContainsString(
            'Telepon 0341-000.',
            $res->json('data.blocks.0.data.body'),
        );
        $this->assertSame(1, count($res->json('data.blocks')));
    }

    public function test_inactive_block_is_omitted_from_the_page(): void
    {
        $block = ReusableBlock::create([
            'name' => 'Blok Nonaktif',
            'is_active' => false,
            'content' => $this->richTextContent('<p>Tidak boleh tampil.</p>'),
        ]);

        $page = Page::create([
            'title' => 'Uji Nonaktif',
            'slug' => 'uji-nonaktif',
            'is_published' => true,
            'blocks' => [[
                'type' => BlockTypes::REUSABLE,
                'data' => ['slug' => $block->slug],
            ]],
        ]);

        $this->getJson("/api/v1/pages/{$page->slug}")
            ->assertOk()
            ->assertJsonCount(0, 'data.blocks');
    }

    public function test_reference_to_missing_block_is_dropped_not_broken(): void
    {
        $page = Page::create([
            'title' => 'Rujukan Hilang',
            'slug' => 'rujukan-hilang',
            'is_published' => true,
            'blocks' => [[
                'type' => BlockTypes::REUSABLE,
                'data' => ['slug' => 'tidak-ada'],
            ]],
        ]);

        $this->getJson("/api/v1/pages/{$page->slug}")
            ->assertOk()
            ->assertJsonCount(0, 'data.blocks');
    }
}
