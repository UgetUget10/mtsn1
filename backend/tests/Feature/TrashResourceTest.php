<?php

namespace Tests\Feature;

use App\Filament\Resources\Achievements\Pages\ListAchievements;
use App\Filament\Resources\Agendas\Pages\ListAgendas;
use App\Filament\Resources\Documents\Pages\ListDocuments;
use App\Filament\Resources\Extracurriculars\Pages\ListExtracurriculars;
use App\Filament\Resources\Galleries\Pages\ListGalleries;
use App\Models\Achievement;
use App\Models\Agenda;
use App\Models\Document;
use App\Models\Extracurricular;
use App\Models\Gallery;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Kelima model ini memakai SoftDeletes tapi tabel Filament-nya dulu tidak punya
 * TrashedFilter/Restore/ForceDelete — record terhapus hilang dari UI dan
 * mustahil dipulihkan. Test ini menjaga fitur "Trash" ala WordPress tetap utuh.
 */
class TrashResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(RoleSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $this->actingAs($user);
    }

    /** @return array<string, array{class-string, class-string<Model>, array<string, mixed>}> */
    public static function resourceProvider(): array
    {
        return [
            'Galeri' => [ListGalleries::class, Gallery::class, ['title' => 'Galeri Uji']],
            // `documents.file` NOT NULL — fixture wajib mengisinya.
            'Dokumen' => [ListDocuments::class, Document::class, [
                'title' => 'Dokumen Uji',
                'file' => 'documents/uji.pdf',
            ]],
            'Agenda' => [ListAgendas::class, Agenda::class, [
                'title' => 'Agenda Uji',
                'start_at' => '2026-07-01 08:00:00',
            ]],
            'Prestasi' => [ListAchievements::class, Achievement::class, [
                'title' => 'Prestasi Uji',
                'year' => 2026,
            ]],
            'Ekstrakurikuler' => [ListExtracurriculars::class, Extracurricular::class, [
                'name' => 'Ekskul Uji',
            ]],
        ];
    }

    /**
     * @param  class-string  $listPage
     * @param  class-string<Model>  $model
     * @param  array<string, mixed>  $attrs
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('resourceProvider')]
    public function test_trashed_record_can_be_seen_and_restored(
        string $listPage,
        string $model,
        array $attrs,
    ): void {
        $record = $model::create($attrs);
        $record->delete();

        $this->assertNotNull($record->fresh()->deleted_at, 'record harus ter-soft-delete');

        Livewire::test($listPage)
            ->filterTable('trashed', true)
            ->assertCanSeeTableRecords([$record])
            ->callTableBulkAction(RestoreBulkAction::class, [$record]);

        $this->assertNull(
            $model::withTrashed()->find($record->getKey())->deleted_at,
            'record harus pulih setelah RestoreBulkAction',
        );
    }

    /**
     * @param  class-string  $listPage
     * @param  class-string<Model>  $model
     * @param  array<string, mixed>  $attrs
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('resourceProvider')]
    public function test_trashed_record_can_be_force_deleted(
        string $listPage,
        string $model,
        array $attrs,
    ): void {
        $record = $model::create($attrs);
        $record->delete();

        Livewire::test($listPage)
            ->filterTable('trashed', true)
            ->callTableBulkAction(ForceDeleteBulkAction::class, [$record]);

        $this->assertNull(
            $model::withTrashed()->find($record->getKey()),
            'record harus hilang permanen setelah ForceDeleteBulkAction',
        );
    }

    /** Tanpa filter, daftar default tetap menyembunyikan item Trash (perilaku WP). */
    #[\PHPUnit\Framework\Attributes\DataProvider('resourceProvider')]
    public function test_default_listing_hides_trashed_records(
        string $listPage,
        string $model,
        array $attrs,
    ): void {
        $visible = $model::create($attrs);
        $trashed = $model::create($attrs);
        $trashed->delete();

        Livewire::test($listPage)
            ->assertCanSeeTableRecords([$visible])
            ->assertCanNotSeeTableRecords([$trashed]);
    }
}
