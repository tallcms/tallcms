<?php

declare(strict_types=1);

namespace TallCms\Cms\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Contracts\Auth\Authenticatable;
use TallCms\Cms\Models\MediaCollection;

class MediaCollectionPolicy
{
    use HandlesAuthorization;

    public function viewAny(Authenticatable $user): bool
    {
        return $user->can('ViewAny:TallcmsMedia');
    }

    public function view(Authenticatable $user, MediaCollection $collection): bool
    {
        return $user->can('View:TallcmsMedia') && $this->ownsOrSuperAdmin($user, $collection);
    }

    public function create(Authenticatable $user): bool
    {
        return $user->can('Create:TallcmsMedia');
    }

    public function update(Authenticatable $user, MediaCollection $collection): bool
    {
        return $user->can('Update:TallcmsMedia') && $this->ownsOrSuperAdmin($user, $collection);
    }

    public function delete(Authenticatable $user, MediaCollection $collection): bool
    {
        return $user->can('Delete:TallcmsMedia') && $this->ownsOrSuperAdmin($user, $collection);
    }

    protected function ownsOrSuperAdmin(Authenticatable $user, MediaCollection $collection): bool
    {
        if (method_exists($user, 'hasRole') && $user->hasRole('super_admin')) {
            return true;
        }

        if (isset($collection->user_id) && $collection->user_id !== null) {
            return $collection->user_id === $user->getAuthIdentifier();
        }

        return true;
    }
}
