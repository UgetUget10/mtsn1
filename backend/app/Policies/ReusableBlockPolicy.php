<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\ReusableBlock;
use Illuminate\Auth\Access\HandlesAuthorization;

class ReusableBlockPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ReusableBlock');
    }

    public function view(AuthUser $authUser, ReusableBlock $reusableBlock): bool
    {
        return $authUser->can('View:ReusableBlock');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ReusableBlock');
    }

    public function update(AuthUser $authUser, ReusableBlock $reusableBlock): bool
    {
        return $authUser->can('Update:ReusableBlock');
    }

    public function delete(AuthUser $authUser, ReusableBlock $reusableBlock): bool
    {
        return $authUser->can('Delete:ReusableBlock');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ReusableBlock');
    }

    public function restore(AuthUser $authUser, ReusableBlock $reusableBlock): bool
    {
        return $authUser->can('Restore:ReusableBlock');
    }

    public function forceDelete(AuthUser $authUser, ReusableBlock $reusableBlock): bool
    {
        return $authUser->can('ForceDelete:ReusableBlock');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ReusableBlock');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ReusableBlock');
    }

    public function replicate(AuthUser $authUser, ReusableBlock $reusableBlock): bool
    {
        return $authUser->can('Replicate:ReusableBlock');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ReusableBlock');
    }

}