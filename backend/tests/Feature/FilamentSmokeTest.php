<?php

namespace Tests\Feature;

use App\Filament\Pages\HomepageBuilder;
use App\Filament\Pages\ManageSiteSettings;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class FilamentSmokeTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsSuperAdmin(): User
    {
        // RefreshDatabase truncate ulang tabel permission tiap test, tapi spatie/laravel-permission
        // meng-cache pemetaan role/permission — bersihkan agar seed baru langsung terpakai.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->seed(RoleSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $this->actingAs($user);

        return $user;
    }

    public function test_all_resource_list_pages_render(): void
    {
        $this->actingAsSuperAdmin();

        foreach (Filament::getPanel('admin')->getResources() as $resource) {
            $pages = $resource::getPages();

            try {
                Livewire::test($pages['index']->getPage())->assertOk();
            } catch (\Throwable $e) {
                $this->fail("index: {$resource} — ".$e->getMessage());
            }

            if (isset($pages['create'])) {
                try {
                    Livewire::test($pages['create']->getPage())->assertOk();
                } catch (\Throwable $e) {
                    $this->fail("create: {$resource} — ".$e->getMessage());
                }
            }
        }
    }

    /**
     * Halaman Edit Post & Page memuat nilai `meta` yang sudah tersimpan ke
     * metabox SEO (termasuk pratinjau cuplikan Alpine). Kalau schema/Blade-nya
     * rusak, hanya render Edit yang menangkapnya — create memakai state kosong.
     */
    public function test_edit_pages_render_with_existing_seo_meta(): void
    {
        $user = $this->actingAsSuperAdmin();

        $meta = [
            'seo_title' => 'Judul SEO',
            'seo_description' => 'Deskripsi SEO.',
            'canonical' => 'https://contoh.test/x',
            'noindex' => true,
            'og_image' => 'seo/contoh.jpg',
        ];

        $post = \App\Models\Post::create([
            'title' => 'Berita Uji',
            'body' => '<p>Isi.</p>',
            'status' => \App\Models\Post::STATUS_PUBLISHED,
            'published_at' => now()->subDay(),
            'user_id' => $user->id,
            'meta' => $meta,
        ]);

        Livewire::test(
            \App\Filament\Resources\Posts\Pages\EditPost::class,
            ['record' => $post->slug],
        )->assertOk();

        $page = \App\Models\Page::create([
            'title' => 'Halaman Uji',
            'slug' => 'halaman-uji',
            'is_published' => true,
            'meta' => $meta,
        ]);

        Livewire::test(
            \App\Filament\Resources\Pages\Pages\EditPage::class,
            ['record' => $page->slug],
        )->assertOk();
    }

    public function test_settings_page_renders(): void
    {
        $this->actingAsSuperAdmin();

        Livewire::test(ManageSiteSettings::class)->assertOk();
    }

    public function test_homepage_builder_page_renders(): void
    {
        $this->actingAsSuperAdmin();

        Livewire::test(HomepageBuilder::class)->assertOk();
    }
}
