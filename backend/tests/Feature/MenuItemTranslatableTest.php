<?php

namespace Tests\Feature;

use App\Filament\Resources\Menus\MenuResource;
use App\Filament\Resources\Menus\RelationManagers\ItemsRelationManager;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class MenuItemTranslatableTest extends TestCase
{
    use RefreshDatabase;

    public function test_menu_item_label_saves_both_locales_via_relation_manager(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(RoleSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $this->actingAs($user);

        $menu = Menu::create(['key' => 'test-menu', 'label' => 'Test Menu']);

        Livewire::test(ItemsRelationManager::class, [
            'ownerRecord' => $menu,
            'pageClass' => MenuResource\Pages\EditMenu::class,
        ])
            ->mountTableAction('create')
            ->setTableActionData([
                'label' => ['id' => 'Beranda', 'en' => 'Home'],
                'type' => 'custom_url',
                'url' => '/',
                'order' => 0,
                'is_active' => true,
            ])
            ->callMountedTableAction();

        $item = MenuItem::where('menu_id', $menu->id)->firstOrFail();
        $this->assertSame('Beranda', $item->getTranslation('label', 'id'));
        $this->assertSame('Home', $item->getTranslation('label', 'en'));

        // Edit ulang: hanya ubah locale id, locale en tidak boleh hilang.
        Livewire::test(ItemsRelationManager::class, [
            'ownerRecord' => $menu,
            'pageClass' => MenuResource\Pages\EditMenu::class,
        ])
            ->mountTableAction('edit', $item)
            ->assertTableActionDataSet([
                'label' => ['id' => 'Beranda', 'en' => 'Home'],
            ])
            ->setTableActionData([
                'label' => ['id' => 'Beranda Diperbarui', 'en' => 'Home'],
                'type' => 'custom_url',
                'url' => '/',
                'order' => 0,
                'is_active' => true,
            ])
            ->callMountedTableAction();

        $item->refresh();
        $this->assertSame('Beranda Diperbarui', $item->getTranslation('label', 'id'));
        $this->assertSame('Home', $item->getTranslation('label', 'en'), 'Locale en tidak boleh hilang saat edit.');
    }
}
