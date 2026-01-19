<?php

declare(strict_types=1);

namespace TallCms\Cms\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Contracts\Auth\Authenticatable;
use Spatie\Permission\Models\Role;

class RolePolicy
{
    use HandlesAuthorization;

    public function viewAny(Authenticatable $authUser): bool
    {
        return $authUser->can('ViewAny:Role');
    }

    public function view(Authenticatable $authUser, Role $role): bool
    {
        return $authUser->can('View:Role');
    }

    public function create(Authenticatable $authUser): bool
    {
        return $authUser->can('Create:Role');
    }

    public function update(Authenticatable $authUser, Role $role): bool
    {
        return $authUser->can('Update:Role');
    }

    public function delete(Authenticatable $authUser, Role $role): bool
    {
        return $authUser->can('Delete:Role');
    }

    public function restore(Authenticatable $authUser, Role $role): bool
    {
        return $authUser->can('Restore:Role');
    }

    public function forceDelete(Authenticatable $authUser, Role $role): bool
    {
        return $authUser->can('ForceDelete:Role');
    }

    public function forceDeleteAny(Authenticatable $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Role');
    }

    public function restoreAny(Authenticatable $authUser): bool
    {
        return $authUser->can('RestoreAny:Role');
    }

    public function replicate(Authenticatable $authUser, Role $role): bool
    {
        return $authUser->can('Replicate:Role');
    }

    public function reorder(Authenticatable $authUser): bool
    {
        return $authUser->can('Reorder:Role');
    }
}
