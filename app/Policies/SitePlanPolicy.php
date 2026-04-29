<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Tallcms\Multisite\Models\SitePlan;
use Illuminate\Auth\Access\HandlesAuthorization;

class SitePlanPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:SitePlan');
    }

    public function view(AuthUser $authUser, SitePlan $sitePlan): bool
    {
        return $authUser->can('View:SitePlan');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:SitePlan');
    }

    public function update(AuthUser $authUser, SitePlan $sitePlan): bool
    {
        return $authUser->can('Update:SitePlan');
    }

    public function delete(AuthUser $authUser, SitePlan $sitePlan): bool
    {
        return $authUser->can('Delete:SitePlan');
    }

    public function restore(AuthUser $authUser, SitePlan $sitePlan): bool
    {
        return $authUser->can('Restore:SitePlan');
    }

    public function forceDelete(AuthUser $authUser, SitePlan $sitePlan): bool
    {
        return $authUser->can('ForceDelete:SitePlan');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:SitePlan');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:SitePlan');
    }

    public function replicate(AuthUser $authUser, SitePlan $sitePlan): bool
    {
        return $authUser->can('Replicate:SitePlan');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:SitePlan');
    }

}