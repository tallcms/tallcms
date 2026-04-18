<?php

declare(strict_types=1);

namespace TallCms\Cms\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Contracts\Auth\Authenticatable;
use TallCms\Cms\Models\CmsCategory;

class CmsCategoryPolicy
{
    use HandlesAuthorization;

    public function viewAny(Authenticatable $user): bool
    {
        return $user->can('ViewAny:CmsCategory');
    }

    public function view(Authenticatable $user, CmsCategory $cmsCategory): bool
    {
        return $user->can('View:CmsCategory') && $this->ownsOrSuperAdmin($user, $cmsCategory);
    }

    public function create(Authenticatable $user): bool
    {
        return $user->can('Create:CmsCategory');
    }

    public function update(Authenticatable $user, CmsCategory $cmsCategory): bool
    {
        return $user->can('Update:CmsCategory') && $this->ownsOrSuperAdmin($user, $cmsCategory);
    }

    public function delete(Authenticatable $user, CmsCategory $cmsCategory): bool
    {
        return $user->can('Delete:CmsCategory') && $this->ownsOrSuperAdmin($user, $cmsCategory);
    }

    public function restore(Authenticatable $user, CmsCategory $cmsCategory): bool
    {
        return $user->can('Restore:CmsCategory') && $this->ownsOrSuperAdmin($user, $cmsCategory);
    }

    public function forceDelete(Authenticatable $user, CmsCategory $cmsCategory): bool
    {
        return $user->can('ForceDelete:CmsCategory') && $this->ownsOrSuperAdmin($user, $cmsCategory);
    }

    public function forceDeleteAny(Authenticatable $user): bool
    {
        return $user->can('ForceDeleteAny:CmsCategory');
    }

    public function restoreAny(Authenticatable $user): bool
    {
        return $user->can('RestoreAny:CmsCategory');
    }

    public function replicate(Authenticatable $user, CmsCategory $cmsCategory): bool
    {
        return $user->can('Replicate:CmsCategory');
    }

    public function reorder(Authenticatable $user): bool
    {
        return $user->can('Reorder:CmsCategory');
    }

    protected function ownsOrSuperAdmin(Authenticatable $user, CmsCategory $cmsCategory): bool
    {
        if (method_exists($user, 'hasRole') && $user->hasRole('super_admin')) {
            return true;
        }

        if (isset($cmsCategory->user_id) && $cmsCategory->user_id !== null) {
            return $cmsCategory->user_id === $user->getAuthIdentifier();
        }

        return true; // No ownership column yet (standalone/pre-migration)
    }
}
