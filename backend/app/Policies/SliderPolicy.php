<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Slider;
use Illuminate\Auth\Access\HandlesAuthorization;

class SliderPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Slider');
    }

    public function view(AuthUser $authUser, Slider $slider): bool
    {
        return $authUser->can('View:Slider');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Slider');
    }

    public function update(AuthUser $authUser, Slider $slider): bool
    {
        return $authUser->can('Update:Slider');
    }

    public function delete(AuthUser $authUser, Slider $slider): bool
    {
        return $authUser->can('Delete:Slider');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Slider');
    }

    public function restore(AuthUser $authUser, Slider $slider): bool
    {
        return $authUser->can('Restore:Slider');
    }

    public function forceDelete(AuthUser $authUser, Slider $slider): bool
    {
        return $authUser->can('ForceDelete:Slider');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Slider');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Slider');
    }

    public function replicate(AuthUser $authUser, Slider $slider): bool
    {
        return $authUser->can('Replicate:Slider');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Slider');
    }

}