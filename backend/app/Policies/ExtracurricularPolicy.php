<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Extracurricular;
use Illuminate\Auth\Access\HandlesAuthorization;

class ExtracurricularPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Extracurricular');
    }

    public function view(AuthUser $authUser, Extracurricular $extracurricular): bool
    {
        return $authUser->can('View:Extracurricular');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Extracurricular');
    }

    public function update(AuthUser $authUser, Extracurricular $extracurricular): bool
    {
        return $authUser->can('Update:Extracurricular');
    }

    public function delete(AuthUser $authUser, Extracurricular $extracurricular): bool
    {
        return $authUser->can('Delete:Extracurricular');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Extracurricular');
    }

    public function restore(AuthUser $authUser, Extracurricular $extracurricular): bool
    {
        return $authUser->can('Restore:Extracurricular');
    }

    public function forceDelete(AuthUser $authUser, Extracurricular $extracurricular): bool
    {
        return $authUser->can('ForceDelete:Extracurricular');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Extracurricular');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Extracurricular');
    }

    public function replicate(AuthUser $authUser, Extracurricular $extracurricular): bool
    {
        return $authUser->can('Replicate:Extracurricular');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Extracurricular');
    }

}