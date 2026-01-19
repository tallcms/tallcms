<?php

declare(strict_types=1);

namespace TallCms\Cms\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable;
use Spatie\Permission\Models\Role;

class RolePolicy
{
    use HandlesAuthorization;

    public function viewAny(Authenticatable&Authorizable $user): bool
    {
        return $user->can('ViewAny:Role');
    }

    public function view(Authenticatable&Authorizable $user, Role $role): bool
    {
        return $user->can('View:Role');
    }

    public function create(Authenticatable&Authorizable $user): bool
    {
        return $user->can('Create:Role');
    }

    public function update(Authenticatable&Authorizable $user, Role $role): bool
    {
        return $user->can('Update:Role');
    }

    public function delete(Authenticatable&Authorizable $user, Role $role): bool
    {
        return $user->can('Delete:Role');
    }

    public function restore(Authenticatable&Authorizable $user, Role $role): bool
    {
        return $user->can('Restore:Role');
    }

    public function forceDelete(Authenticatable&Authorizable $user, Role $role): bool
    {
        return $user->can('ForceDelete:Role');
    }

    public function forceDeleteAny(Authenticatable&Authorizable $user): bool
    {
        return $user->can('ForceDeleteAny:Role');
    }

    public function restoreAny(Authenticatable&Authorizable $user): bool
    {
        return $user->can('RestoreAny:Role');
    }

    public function replicate(Authenticatable&Authorizable $user, Role $role): bool
    {
        return $user->can('Replicate:Role');
    }

    public function reorder(Authenticatable&Authorizable $user): bool
    {
        return $user->can('Reorder:Role');
    }
}
