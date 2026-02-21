<?php

declare(strict_types=1);

namespace TallCms\Cms\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Contracts\Auth\Authenticatable;
use TallCms\Cms\Models\CmsComment;

class CmsCommentPolicy
{
    use HandlesAuthorization;

    public function viewAny(Authenticatable $user): bool
    {
        return $user->can('ViewAny:CmsComment');
    }

    public function view(Authenticatable $user, CmsComment $cmsComment): bool
    {
        return $user->can('View:CmsComment');
    }

    public function create(Authenticatable $user): bool
    {
        return $user->can('Create:CmsComment');
    }

    public function update(Authenticatable $user, CmsComment $cmsComment): bool
    {
        return $user->can('Update:CmsComment');
    }

    public function delete(Authenticatable $user, CmsComment $cmsComment): bool
    {
        return $user->can('Delete:CmsComment');
    }

    public function restore(Authenticatable $user, CmsComment $cmsComment): bool
    {
        return $user->can('Restore:CmsComment');
    }

    public function forceDelete(Authenticatable $user, CmsComment $cmsComment): bool
    {
        return $user->can('ForceDelete:CmsComment');
    }

    public function forceDeleteAny(Authenticatable $user): bool
    {
        return $user->can('ForceDeleteAny:CmsComment');
    }

    public function restoreAny(Authenticatable $user): bool
    {
        return $user->can('RestoreAny:CmsComment');
    }

    public function replicate(Authenticatable $user, CmsComment $cmsComment): bool
    {
        return $user->can('Replicate:CmsComment');
    }

    public function reorder(Authenticatable $user): bool
    {
        return $user->can('Reorder:CmsComment');
    }

    public function approve(Authenticatable $user): bool
    {
        return $user->can('Approve:CmsComment');
    }

    public function reject(Authenticatable $user): bool
    {
        return $user->can('Reject:CmsComment');
    }

    public function markAsSpam(Authenticatable $user): bool
    {
        return $user->can('MarkAsSpam:CmsComment');
    }
}
