<?php

declare(strict_types=1);

namespace TallCms\Cms\Policies;

use TallCms\Cms\Models\CmsCategory;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Contracts\Auth\Authenticatable;

class CmsCategoryPolicy
{
    use HandlesAuthorization;

    public function viewAny(Authenticatable $user): bool
    {
        return $user->can('ViewAny:CmsCategory');
    }

    public function view(Authenticatable $user, CmsCategory $cmsCategory): bool
    {
        return $user->can('View:CmsCategory');
    }

    public function create(Authenticatable $user): bool
    {
        return $user->can('Create:CmsCategory');
    }

    public function update(Authenticatable $user, CmsCategory $cmsCategory): bool
    {
        return $user->can('Update:CmsCategory');
    }

    public function delete(Authenticatable $user, CmsCategory $cmsCategory): bool
    {
        return $user->can('Delete:CmsCategory');
    }

    public function restore(Authenticatable $user, CmsCategory $cmsCategory): bool
    {
        return $user->can('Restore:CmsCategory');
    }

    public function forceDelete(Authenticatable $user, CmsCategory $cmsCategory): bool
    {
        return $user->can('ForceDelete:CmsCategory');
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
}
