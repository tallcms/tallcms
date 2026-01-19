<?php

declare(strict_types=1);

namespace TallCms\Cms\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Contracts\Auth\Authenticatable;

class UserPolicy
{
    use HandlesAuthorization;

    public function viewAny(Authenticatable $authUser): bool
    {
        return $authUser->can('ViewAny:User');
    }

    public function view(Authenticatable $authUser): bool
    {
        return $authUser->can('View:User');
    }

    public function create(Authenticatable $authUser): bool
    {
        return $authUser->can('Create:User');
    }

    public function update(Authenticatable $authUser): bool
    {
        return $authUser->can('Update:User');
    }

    public function delete(Authenticatable $authUser): bool
    {
        return $authUser->can('Delete:User');
    }

    public function restore(Authenticatable $authUser): bool
    {
        return $authUser->can('Restore:User');
    }

    public function forceDelete(Authenticatable $authUser): bool
    {
        return $authUser->can('ForceDelete:User');
    }

    public function forceDeleteAny(Authenticatable $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:User');
    }

    public function restoreAny(Authenticatable $authUser): bool
    {
        return $authUser->can('RestoreAny:User');
    }

    public function deleteAny(Authenticatable $authUser): bool
    {
        return $authUser->can('DeleteAny:User');
    }
}
