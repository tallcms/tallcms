<?php

declare(strict_types=1);

namespace TallCms\Cms\Policies;

use TallCms\Cms\Models\CmsPost;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class CmsPostPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:CmsPost');
    }

    public function view(AuthUser $authUser, CmsPost $cmsPost): bool
    {
        return $authUser->can('View:CmsPost');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:CmsPost');
    }

    public function update(AuthUser $authUser, CmsPost $cmsPost): bool
    {
        return $authUser->can('Update:CmsPost');
    }

    public function delete(AuthUser $authUser, CmsPost $cmsPost): bool
    {
        return $authUser->can('Delete:CmsPost');
    }

    public function restore(AuthUser $authUser, CmsPost $cmsPost): bool
    {
        return $authUser->can('Restore:CmsPost');
    }

    public function forceDelete(AuthUser $authUser, CmsPost $cmsPost): bool
    {
        return $authUser->can('ForceDelete:CmsPost');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:CmsPost');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:CmsPost');
    }

    public function replicate(AuthUser $authUser, CmsPost $cmsPost): bool
    {
        return $authUser->can('Replicate:CmsPost');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:CmsPost');
    }

    /**
     * Determine if the user can approve the post (publish pending content)
     */
    public function approve(AuthUser $authUser, CmsPost $cmsPost): bool
    {
        return $authUser->can('Approve:CmsPost') && $cmsPost->canBeApproved();
    }

    /**
     * Determine if the user can submit the post for review
     */
    public function submitForReview(AuthUser $authUser, CmsPost $cmsPost): bool
    {
        return $authUser->can('SubmitForReview:CmsPost') && $cmsPost->canSubmitForReview();
    }

    /**
     * Determine if the user can view revisions
     */
    public function viewRevisions(AuthUser $authUser, CmsPost $cmsPost): bool
    {
        return $authUser->can('ViewRevisions:CmsPost');
    }

    /**
     * Determine if the user can restore a revision
     */
    public function restoreRevision(AuthUser $authUser, CmsPost $cmsPost): bool
    {
        return $authUser->can('RestoreRevision:CmsPost');
    }

    /**
     * Determine if the user can generate preview links
     */
    public function generatePreviewLink(AuthUser $authUser, CmsPost $cmsPost): bool
    {
        return $authUser->can('GeneratePreviewLink:CmsPost');
    }
}
