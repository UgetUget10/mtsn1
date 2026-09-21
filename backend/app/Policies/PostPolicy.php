<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Post;
use Illuminate\Auth\Access\HandlesAuthorization;

class PostPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Post');
    }

    public function view(AuthUser $authUser, Post $post): bool
    {
        return $authUser->can('View:Post');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Post');
    }

    /**
     * Kontributor hanya boleh menyunting Berita miliknya sendiri (dibatasi
     * lebih lanjut lewat status yang boleh dipilih — lihat Post::assignableStatuses());
     * editor/super_admin dengan permission Update:Post bebas menyunting semua.
     */
    public function update(AuthUser $authUser, Post $post): bool
    {
        if (! $authUser->can('Update:Post')) {
            return false;
        }

        if (method_exists($authUser, 'hasRole') && $authUser->hasRole('kontributor') && ! $authUser->hasRole(['editor', 'super_admin'])) {
            return $post->user_id === $authUser->getKey();
        }

        return true;
    }

    /** Kontributor tidak boleh menghapus Berita sama sekali (termasuk miliknya sendiri). */
    public function delete(AuthUser $authUser, Post $post): bool
    {
        if (! $authUser->can('Delete:Post')) {
            return false;
        }

        if (method_exists($authUser, 'hasRole') && $authUser->hasRole('kontributor') && ! $authUser->hasRole(['editor', 'super_admin'])) {
            return false;
        }

        return true;
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Post');
    }

    public function restore(AuthUser $authUser, Post $post): bool
    {
        return $authUser->can('Restore:Post');
    }

    public function forceDelete(AuthUser $authUser, Post $post): bool
    {
        return $authUser->can('ForceDelete:Post');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Post');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Post');
    }

    public function replicate(AuthUser $authUser, Post $post): bool
    {
        return $authUser->can('Replicate:Post');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Post');
    }

}