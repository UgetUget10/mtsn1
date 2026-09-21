<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Models\Role;

/**
 * Staf yang berhak menerima notifikasi editorial (tinjau berita, pesan kontak):
 * pemegang role `editor` atau `super_admin`. Dipakai oleh App\Notifications\*.
 */
class EditorialStaff
{
    /** @return Collection<int, User> */
    public static function all(): Collection
    {
        // Hanya query role yang benar-benar ada — `User::role()` melempar
        // RoleDoesNotExist bila salah satu nama tidak terdaftar (mis. di DB
        // test yang minimal). Jadi saring dulu.
        $roles = Role::whereIn('name', ['editor', 'super_admin'])
            ->pluck('name')
            ->all();

        if ($roles === []) {
            return new Collection;
        }

        return User::role($roles)->get();
    }
}
