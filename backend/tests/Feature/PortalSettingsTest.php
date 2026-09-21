<?php

namespace Tests\Feature;

use App\Filament\Pages\ManageSiteSettings;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * `survey_url`, `sakip_url`, dan `pmbm_url` sudah lama ada di
 * MiscController::PUBLIC_SETTING_KEYS dan dipakai frontend, tapi tidak punya
 * field di panel — nilainya selalu kosong di produksi.
 */
class PortalSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('public-api');
        Cache::forget('settings.all');
    }

    private function actingAsSuperAdmin(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(RoleSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $this->actingAs($user);
    }

    public function test_portal_links_can_be_saved_from_the_panel(): void
    {
        $this->actingAsSuperAdmin();

        Livewire::test(ManageSiteSettings::class)
            ->fillForm([
                'sakip_url' => 'https://sakip.test/mtsn1',
                'survey_url' => 'https://survei.test/mtsn1',
                'pmbm_url' => 'https://pmbm.test/mtsn1',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('settings', ['key' => 'sakip_url', 'value' => 'https://sakip.test/mtsn1']);
        $this->assertDatabaseHas('settings', ['key' => 'survey_url', 'value' => 'https://survei.test/mtsn1']);
        $this->assertDatabaseHas('settings', ['key' => 'pmbm_url', 'value' => 'https://pmbm.test/mtsn1']);
    }

    /**
     * Regresi terpenting: nilai harus MUNCUL KEMBALI saat form dimuat ulang.
     * Field tanpa entri di properti $keys tersimpan tapi tak pernah dimuat —
     * dari sisi admin tampak "hilang" setelah reload.
     */
    public function test_saved_links_are_reloaded_into_the_form(): void
    {
        $this->actingAsSuperAdmin();

        Setting::create(['key' => 'survey_url', 'value' => 'https://survei.test/x']);
        Setting::create(['key' => 'sakip_url', 'value' => 'https://sakip.test/x']);
        Cache::forget('settings.all');

        Livewire::test(ManageSiteSettings::class)
            ->assertFormSet([
                'survey_url' => 'https://survei.test/x',
                'sakip_url' => 'https://sakip.test/x',
            ]);
    }

    public function test_links_are_exposed_by_the_public_settings_endpoint(): void
    {
        Setting::create(['key' => 'survey_url', 'value' => 'https://survei.test/y']);
        Setting::create(['key' => 'sakip_url', 'value' => 'https://sakip.test/y']);
        Cache::forget('settings.all');

        $this->getJson('/api/v1/settings')
            ->assertOk()
            ->assertJsonPath('survey_url', 'https://survei.test/y')
            ->assertJsonPath('sakip_url', 'https://sakip.test/y');
    }
}
