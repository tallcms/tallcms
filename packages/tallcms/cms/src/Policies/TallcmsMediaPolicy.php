<?php

declare(strict_types=1);

namespace TallCms\Cms\Policies;

use TallCms\Cms\Models\TallcmsMedia;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Contracts\Auth\Authenticatable;

class TallcmsMediaPolicy
{
    use HandlesAuthorization;

    public function viewAny(Authenticatable $user): bool
    {
        return $user->can('ViewAny:TallcmsMedia');
    }

    public function view(Authenticatable $user, TallcmsMedia $tallcmsMedia): bool
    {
        return $user->can('View:TallcmsMedia');
    }

    public function create(Authenticatable $user): bool
    {
        return $user->can('Create:TallcmsMedia');
    }

    public function update(Authenticatable $user, TallcmsMedia $tallcmsMedia): bool
    {
        return $user->can('Update:TallcmsMedia');
    }

    public function delete(Authenticatable $user, TallcmsMedia $tallcmsMedia): bool
    {
        return $user->can('Delete:TallcmsMedia');
    }

    public function restore(Authenticatable $user, TallcmsMedia $tallcmsMedia): bool
    {
        return $user->can('Restore:TallcmsMedia');
    }

    public function forceDelete(Authenticatable $user, TallcmsMedia $tallcmsMedia): bool
    {
        return $user->can('ForceDelete:TallcmsMedia');
    }

    public function forceDeleteAny(Authenticatable $user): bool
    {
        return $user->can('ForceDeleteAny:TallcmsMedia');
    }

    public function restoreAny(Authenticatable $user): bool
    {
        return $user->can('RestoreAny:TallcmsMedia');
    }

    public function replicate(Authenticatable $user, TallcmsMedia $tallcmsMedia): bool
    {
        return $user->can('Replicate:TallcmsMedia');
    }

    public function reorder(Authenticatable $user): bool
    {
        return $user->can('Reorder:TallcmsMedia');
    }
}
