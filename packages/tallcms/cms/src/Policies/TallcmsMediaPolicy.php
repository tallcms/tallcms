<?php

declare(strict_types=1);

namespace TallCms\Cms\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Contracts\Auth\Authenticatable;
use TallCms\Cms\Models\TallcmsMedia;

class TallcmsMediaPolicy
{
    use HandlesAuthorization;

    public function viewAny(Authenticatable $user): bool
    {
        return $user->can('ViewAny:TallcmsMedia');
    }

    public function view(Authenticatable $user, TallcmsMedia $tallcmsMedia): bool
    {
        return $user->can('View:TallcmsMedia') && $this->ownsOrSuperAdmin($user, $tallcmsMedia);
    }

    public function create(Authenticatable $user): bool
    {
        return $user->can('Create:TallcmsMedia');
    }

    public function update(Authenticatable $user, TallcmsMedia $tallcmsMedia): bool
    {
        return $user->can('Update:TallcmsMedia') && $this->ownsOrSuperAdmin($user, $tallcmsMedia);
    }

    public function delete(Authenticatable $user, TallcmsMedia $tallcmsMedia): bool
    {
        return $user->can('Delete:TallcmsMedia') && $this->ownsOrSuperAdmin($user, $tallcmsMedia);
    }

    public function restore(Authenticatable $user, TallcmsMedia $tallcmsMedia): bool
    {
        return $user->can('Restore:TallcmsMedia') && $this->ownsOrSuperAdmin($user, $tallcmsMedia);
    }

    public function forceDelete(Authenticatable $user, TallcmsMedia $tallcmsMedia): bool
    {
        return $user->can('ForceDelete:TallcmsMedia') && $this->ownsOrSuperAdmin($user, $tallcmsMedia);
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

    protected function ownsOrSuperAdmin(Authenticatable $user, TallcmsMedia $tallcmsMedia): bool
    {
        if (method_exists($user, 'hasRole') && $user->hasRole('super_admin')) {
            return true;
        }

        if (isset($tallcmsMedia->user_id) && $tallcmsMedia->user_id !== null) {
            return $tallcmsMedia->user_id === $user->getAuthIdentifier();
        }

        return true; // No ownership column yet (standalone/pre-migration)
    }
}
