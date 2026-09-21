<?php

namespace Tests\Feature;

use App\Filament\Pages\HomepageBuilder;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class HomepageBuilderSaveTest extends TestCase
{
    use RefreshDatabase;

    public function test_toggling_a_section_persists_to_settings(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(RoleSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $this->actingAs($user);

        $component = Livewire::test(HomepageBuilder::class);

        $sections = $component->get('data.sections');
        $firstKey = array_key_first($sections);
        $sections[$firstKey]['is_visible'] = false;

        $component->set('data.sections', $sections)->call('save');

        $stored = json_decode(Setting::where('key', 'homepage_sections')->value('value'), true);
        $this->assertNotEmpty($stored);
        $this->assertFalse(collect($stored)->firstWhere('id', $sections[$firstKey]['id'])['is_visible']);
    }
}
