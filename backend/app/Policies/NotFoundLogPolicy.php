<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\NotFoundLog;
use Illuminate\Auth\Access\HandlesAuthorization;

class NotFoundLogPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:NotFoundLog');
    }

    public function view(AuthUser $authUser, NotFoundLog $notFoundLog): bool
    {
        return $authUser->can('View:NotFoundLog');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:NotFoundLog');
    }

    public function update(AuthUser $authUser, NotFoundLog $notFoundLog): bool
    {
        return $authUser->can('Update:NotFoundLog');
    }

    public function delete(AuthUser $authUser, NotFoundLog $notFoundLog): bool
    {
        return $authUser->can('Delete:NotFoundLog');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:NotFoundLog');
    }

    public function restore(AuthUser $authUser, NotFoundLog $notFoundLog): bool
    {
        return $authUser->can('Restore:NotFoundLog');
    }

    public function forceDelete(AuthUser $authUser, NotFoundLog $notFoundLog): bool
    {
        return $authUser->can('ForceDelete:NotFoundLog');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:NotFoundLog');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:NotFoundLog');
    }

    public function replicate(AuthUser $authUser, NotFoundLog $notFoundLog): bool
    {
        return $authUser->can('Replicate:NotFoundLog');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:NotFoundLog');
    }

}