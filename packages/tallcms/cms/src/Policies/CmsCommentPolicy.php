<?php

declare(strict_types=1);

namespace TallCms\Cms\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Contracts\Auth\Authenticatable;
use TallCms\Cms\Models\CmsComment;
use TallCms\Cms\Policies\Concerns\ChecksSiteOwnership;

/**
 * CmsComment authorization.
 *
 * Shield controls role-level access ("can this role touch comments at all?").
 * Record-scoped methods layer a site-ownership check on top so a site_owner
 * can only moderate their own site's comments — without this, any user with
 * the ViewAny/Update permission would see and act on every tenant's comments.
 * Single-site installs bypass the ownership check transparently (the trait
 * returns true when multisite scoping isn't active).
 */
class CmsCommentPolicy
{
    use ChecksSiteOwnership, HandlesAuthorization;

    public function viewAny(Authenticatable $user): bool
    {
        return $user->can('ViewAny:CmsComment');
    }

    public function view(Authenticatable $user, CmsComment $cmsComment): bool
    {
        return $user->can('View:CmsComment')
            && $this->userOwnsContentSite($user, $cmsComment->site_id);
    }

    public function create(Authenticatable $user): bool
    {
        return $user->can('Create:CmsComment');
    }

    public function update(Authenticatable $user, CmsComment $cmsComment): bool
    {
        return $user->can('Update:CmsComment')
            && $this->userOwnsContentSite($user, $cmsComment->site_id);
    }

    public function delete(Authenticatable $user, CmsComment $cmsComment): bool
    {
        return $user->can('Delete:CmsComment')
            && $this->userOwnsContentSite($user, $cmsComment->site_id);
    }

    public function restore(Authenticatable $user, CmsComment $cmsComment): bool
    {
        return $user->can('Restore:CmsComment')
            && $this->userOwnsContentSite($user, $cmsComment->site_id);
    }

    public function forceDelete(Authenticatable $user, CmsComment $cmsComment): bool
    {
        return $user->can('ForceDelete:CmsComment')
            && $this->userOwnsContentSite($user, $cmsComment->site_id);
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
        return $user->can('Replicate:CmsComment')
            && $this->userOwnsContentSite($user, $cmsComment->site_id);
    }

    public function reorder(Authenticatable $user): bool
    {
        return $user->can('Reorder:CmsComment');
    }

    public function approve(Authenticatable $user, CmsComment $cmsComment): bool
    {
        return $user->can('Approve:CmsComment')
            && $this->userOwnsContentSite($user, $cmsComment->site_id);
    }

    public function reject(Authenticatable $user, CmsComment $cmsComment): bool
    {
        return $user->can('Reject:CmsComment')
            && $this->userOwnsContentSite($user, $cmsComment->site_id);
    }

    public function markAsSpam(Authenticatable $user, CmsComment $cmsComment): bool
    {
        return $user->can('MarkAsSpam:CmsComment')
            && $this->userOwnsContentSite($user, $cmsComment->site_id);
    }
}
