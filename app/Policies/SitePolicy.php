<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Tallcms\Multisite\Models\Site;
use Illuminate\Auth\Access\HandlesAuthorization;

class SitePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Site');
    }

    public function view(AuthUser $authUser, Site $site): bool
    {
        return $authUser->can('View:Site');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Site');
    }

    public function update(AuthUser $authUser, Site $site): bool
    {
        return $authUser->can('Update:Site');
    }

    public function delete(AuthUser $authUser, Site $site): bool
    {
        return $authUser->can('Delete:Site');
    }

    public function restore(AuthUser $authUser, Site $site): bool
    {
        return $authUser->can('Restore:Site');
    }

    public function forceDelete(AuthUser $authUser, Site $site): bool
    {
        return $authUser->can('ForceDelete:Site');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Site');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Site');
    }

    public function replicate(AuthUser $authUser, Site $site): bool
    {
        return $authUser->can('Replicate:Site');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Site');
    }

}