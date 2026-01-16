<?php

declare(strict_types=1);

namespace TallCms\Cms\Policies;

use TallCms\Cms\Models\CmsPost;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Contracts\Auth\Authenticatable;

class CmsPostPolicy
{
    use HandlesAuthorization;

    public function viewAny(Authenticatable $user): bool
    {
        return $user->can('ViewAny:CmsPost');
    }

    public function view(Authenticatable $user, CmsPost $cmsPost): bool
    {
        return $user->can('View:CmsPost');
    }

    public function create(Authenticatable $user): bool
    {
        return $user->can('Create:CmsPost');
    }

    public function update(Authenticatable $user, CmsPost $cmsPost): bool
    {
        return $user->can('Update:CmsPost');
    }

    public function delete(Authenticatable $user, CmsPost $cmsPost): bool
    {
        return $user->can('Delete:CmsPost');
    }

    public function restore(Authenticatable $user, CmsPost $cmsPost): bool
    {
        return $user->can('Restore:CmsPost');
    }

    public function forceDelete(Authenticatable $user, CmsPost $cmsPost): bool
    {
        return $user->can('ForceDelete:CmsPost');
    }

    public function forceDeleteAny(Authenticatable $user): bool
    {
        return $user->can('ForceDeleteAny:CmsPost');
    }

    public function restoreAny(Authenticatable $user): bool
    {
        return $user->can('RestoreAny:CmsPost');
    }

    public function replicate(Authenticatable $user, CmsPost $cmsPost): bool
    {
        return $user->can('Replicate:CmsPost');
    }

    public function reorder(Authenticatable $user): bool
    {
        return $user->can('Reorder:CmsPost');
    }

    /**
     * Determine if the user can approve the post (publish pending content)
     */
    public function approve(Authenticatable $user, CmsPost $cmsPost): bool
    {
        return $user->can('Approve:CmsPost') && $cmsPost->canBeApproved();
    }

    /**
     * Determine if the user can submit the post for review
     */
    public function submitForReview(Authenticatable $user, CmsPost $cmsPost): bool
    {
        return $user->can('SubmitForReview:CmsPost') && $cmsPost->canSubmitForReview();
    }

    /**
     * Determine if the user can view revisions
     */
    public function viewRevisions(Authenticatable $user, CmsPost $cmsPost): bool
    {
        return $user->can('ViewRevisions:CmsPost');
    }

    /**
     * Determine if the user can restore a revision
     */
    public function restoreRevision(Authenticatable $user, CmsPost $cmsPost): bool
    {
        return $user->can('RestoreRevision:CmsPost');
    }

    /**
     * Determine if the user can generate preview links
     */
    public function generatePreviewLink(Authenticatable $user, CmsPost $cmsPost): bool
    {
        return $user->can('GeneratePreviewLink:CmsPost');
    }
}
