<?php

declare(strict_types=1);

namespace TallCms\Cms\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable;

class UserPolicy
{
    use HandlesAuthorization;

    public function viewAny(Authenticatable&Authorizable $user): bool
    {
        return $user->can('ViewAny:User');
    }

    public function view(Authenticatable&Authorizable $user): bool
    {
        return $user->can('View:User');
    }

    public function create(Authenticatable&Authorizable $user): bool
    {
        return $user->can('Create:User');
    }

    public function update(Authenticatable&Authorizable $user): bool
    {
        return $user->can('Update:User');
    }

    public function delete(Authenticatable&Authorizable $user): bool
    {
        return $user->can('Delete:User');
    }

    public function restore(Authenticatable&Authorizable $user): bool
    {
        return $user->can('Restore:User');
    }

    public function forceDelete(Authenticatable&Authorizable $user): bool
    {
        return $user->can('ForceDelete:User');
    }

    public function forceDeleteAny(Authenticatable&Authorizable $user): bool
    {
        return $user->can('ForceDeleteAny:User');
    }

    public function restoreAny(Authenticatable&Authorizable $user): bool
    {
        return $user->can('RestoreAny:User');
    }

    public function deleteAny(Authenticatable&Authorizable $user): bool
    {
        return $user->can('DeleteAny:User');
    }
}
