<?php

namespace Tests\Feature;

use App\Filament\Pages\PageBlockEditor;
use App\Filament\Resources\Pages\PageResource;
use App\Models\Page;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PageBlockEditorTest extends TestCase
{
    use RefreshDatabase;

    public function test_canvas_editor_page_loads_for_authorized_user(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(RoleSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $this->actingAs($user);

        $page = Page::create(['title' => 'Akademik', 'slug' => 'akademik-index', 'is_published' => true]);

        $response = $this->get(PageResource::getUrl('canvas', ['record' => $page]));

        $response->assertOk();
    }

    public function test_mounts_block_form_and_saves_new_widget_into_draft(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(RoleSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $this->actingAs($user);

        $page = Page::create(['title' => 'Kontak', 'slug' => 'kontak', 'is_published' => true, 'blocks' => [], 'blocks_draft' => null]);

        $component = Livewire::test(PageBlockEditor::class, [
            'pageSlug' => $page->slug,
            'type' => 'cta',
        ]);

        $component->set('data.heading', 'Ayo Daftar')
            ->set('data.button_label', 'Daftar')
            ->set('data.button_href', 'https://example.com')
            ->set('data.style', 'primary')
            ->call('save');

        $page->refresh();
        $this->assertNotEmpty($page->blocks_draft);
        $this->assertSame(2, $page->blocks_draft['schema']);
        $leaf = $page->blocks_draft['tree'][0]['children'][0]['children'][0];
        $this->assertSame('cta', $leaf['type']);
        $this->assertSame('Ayo Daftar', $leaf['data']['heading']);
    }

    /**
     * $this->page adalah properti Eloquent model bertipe — Livewire men-
     * dehidrasinya hanya sebagai kelas+primary key dan mengambil ULANG dari
     * DB tiap request (lihat Livewire\Features\SupportModels\ModelSynth),
     * jadi save() otomatis membaca blocks_draft TERBARU, bukan snapshot basi
     * dari saat mount(). Test ini mengunci perilaku itu: kalau autosave
     * kanvas React (PUT .../tree, request terpisah) menulis blocks_draft
     * SETELAH modal dibuka tapi SEBELUM tombol Simpan ditekan, save() harus
     * membangun DI ATAS tulisan itu, bukan menimpanya.
     */
    public function test_save_does_not_clobber_draft_written_after_mount(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(RoleSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $this->actingAs($user);

        $page = Page::create(['title' => 'Kontak', 'slug' => 'kontak', 'is_published' => true, 'blocks' => []]);

        // Mount modal untuk widget BARU (belum ada di tree) — meniru saat
        // pengguna baru saja klik "+ Tambah widget".
        $component = Livewire::test(PageBlockEditor::class, [
            'pageSlug' => $page->slug,
            'type' => 'quote',
        ]);

        // Selagi modal terbuka, autosave kanvas React menulis section/kolom
        // baru ke blocks_draft (mis. hasil drag-drop yang terjadi bersamaan).
        $page->update([
            'blocks_draft' => [
                'schema' => 2,
                'tree' => [[
                    'id' => 'sec_from_autosave',
                    'type' => 'section',
                    'children' => [[
                        'id' => 'col_from_autosave',
                        'type' => 'column',
                        'style' => ['base' => ['width' => 12]],
                        'children' => [
                            ['id' => 'wid_from_autosave', 'type' => 'stats', 'data' => ['items' => []]],
                        ],
                    ]],
                ]],
            ],
        ]);

        $component->set('data.text', 'Kutipan baru')
            ->set('data.attribution', null)
            ->call('save');

        $page->refresh();

        // Widget dari autosave (yang terjadi setelah mount) harus TETAP ada.
        $sectionIds = collect($page->blocks_draft['tree'])->pluck('id');
        $this->assertContains('sec_from_autosave', $sectionIds);

        // Widget baru dari modal ikut tersimpan (ditambahkan, bukan menimpa).
        $allLeafTypes = collect($page->blocks_draft['tree'])
            ->flatMap(fn ($s) => $s['children'])
            ->flatMap(fn ($c) => $c['children'])
            ->pluck('type');
        $this->assertContains('quote', $allLeafTypes);
        $this->assertContains('stats', $allLeafTypes);
    }
}
