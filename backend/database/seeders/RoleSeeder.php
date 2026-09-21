<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Buat role dasar panel admin.
     *
     * - super_admin: akses penuh (di-bypass lewat Gate::before Shield, lihat config/filament-shield.php).
     * - editor: kelola semua konten, tanpa akses Settings, Users, atau Roles.
     * - kontributor: hanya boleh mengelola Berita miliknya sendiri, tanpa hak Delete/Publish
     *   (dibatasi lebih lanjut di level resource lewat pengecekan `user_id` pada PostPolicy).
     */
    public function run(): void
    {
        // Pastikan permission Shield ada. Di lingkungan nyata ini dibuat sekali
        // lewat `php artisan shield:generate`, tapi database test (sqlite
        // in-memory) mulai kosong — jadi buat idempotent di sini agar role
        // benar-benar punya permission saat dites, bukan hanya di produksi.
        $this->ensureShieldPermissions();

        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

        $editor = Role::firstOrCreate(['name' => 'editor', 'guard_name' => 'web']);
        $editorExcluded = [
            'User', 'Role', 'Redirect',
        ];
        $editorPermissions = Permission::query()
            ->where('guard_name', 'web')
            ->get()
            ->reject(fn (Permission $p) => collect($editorExcluded)->contains(
                fn ($entity) => str_contains($p->name, ":{$entity}")
            ));
        $editor->syncPermissions($editorPermissions);

        $kontributor = Role::firstOrCreate(['name' => 'kontributor', 'guard_name' => 'web']);
        $kontributorAllowed = [
            'ViewAny:Post', 'View:Post', 'Create:Post', 'Update:Post',
            'ViewAny:Agenda', 'View:Agenda', 'Create:Agenda', 'Update:Agenda',
            'ViewAny:Gallery', 'View:Gallery', 'Create:Gallery', 'Update:Gallery',
        ];
        $kontributor->syncPermissions(
            Permission::query()->whereIn('name', $kontributorAllowed)->where('guard_name', 'web')->get()
        );

        // Assign akun admin bawaan (dibuat DatabaseSeeder) sebagai super_admin.
        User::where('email', 'admin@mtsn1.sch.id')->first()?->assignRole($superAdmin);
    }

    /**
     * Buat permission Shield untuk setiap resource & page panel admin bila
     * belum ada. Nama & format mengikuti config('filament-shield').
     */
    private function ensureShieldPermissions(): void
    {
        if (Permission::query()->where('guard_name', 'web')->exists()) {
            return;
        }

        $prefixes = ['ViewAny', 'View', 'Create', 'Update', 'Delete', 'DeleteAny', 'Restore', 'RestoreAny', 'ForceDelete', 'ForceDeleteAny', 'Replicate', 'Reorder'];

        $entities = [
            'Achievement', 'Agenda', 'Category', 'Comment', 'Contact', 'Document', 'Extracurricular',
            'Gallery', 'Media', 'Menu', 'NotFoundLog', 'Page', 'Post', 'Redirect', 'ReusableBlock', 'Role', 'Slider',
            'Tag', 'Teacher', 'Testimonial', 'User',
        ];

        $permissions = [];
        foreach ($entities as $entity) {
            foreach ($prefixes as $prefix) {
                $permissions[] = ['name' => "{$prefix}:{$entity}", 'guard_name' => 'web'];
            }
        }

        // Permission halaman custom (prefix view saja).
        foreach (['ManageSiteSettings', 'HomepageBuilder'] as $page) {
            $permissions[] = ['name' => "View:{$page}", 'guard_name' => 'web'];
        }

        Permission::insert($permissions);
    }
}
