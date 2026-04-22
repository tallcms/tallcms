<?php

declare(strict_types=1);

namespace TallCms\Cms\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Contracts\Auth\Authenticatable;
use TallCms\Cms\Models\CmsPage;
use TallCms\Cms\Policies\Concerns\ChecksSiteOwnership;

class CmsPagePolicy
{
    use ChecksSiteOwnership;
    use HandlesAuthorization;

    public function viewAny(Authenticatable $user): bool
    {
        return $user->can('ViewAny:CmsPage');
    }

    public function view(Authenticatable $user, CmsPage $cmsPage): bool
    {
        return $user->can('View:CmsPage')
            && $this->userOwnsContentSite($user, $cmsPage->site_id);
    }

    public function create(Authenticatable $user): bool
    {
        return $user->can('Create:CmsPage');
    }

    public function update(Authenticatable $user, CmsPage $cmsPage): bool
    {
        return $user->can('Update:CmsPage')
            && $this->userOwnsContentSite($user, $cmsPage->site_id);
    }

    public function delete(Authenticatable $user, CmsPage $cmsPage): bool
    {
        return $user->can('Delete:CmsPage')
            && $this->userOwnsContentSite($user, $cmsPage->site_id);
    }

    public function restore(Authenticatable $user, CmsPage $cmsPage): bool
    {
        return $user->can('Restore:CmsPage')
            && $this->userOwnsContentSite($user, $cmsPage->site_id);
    }

    public function forceDelete(Authenticatable $user, CmsPage $cmsPage): bool
    {
        return $user->can('ForceDelete:CmsPage')
            && $this->userOwnsContentSite($user, $cmsPage->site_id);
    }

    public function forceDeleteAny(Authenticatable $user): bool
    {
        return $user->can('ForceDeleteAny:CmsPage');
    }

    public function restoreAny(Authenticatable $user): bool
    {
        return $user->can('RestoreAny:CmsPage');
    }

    public function replicate(Authenticatable $user, CmsPage $cmsPage): bool
    {
        return $user->can('Replicate:CmsPage')
            && $this->userOwnsContentSite($user, $cmsPage->site_id);
    }

    public function reorder(Authenticatable $user): bool
    {
        return $user->can('Reorder:CmsPage');
    }

    /**
     * Determine if the user can approve the page (publish pending content)
     */
    public function approve(Authenticatable $user, CmsPage $cmsPage): bool
    {
        return $user->can('Approve:CmsPage')
            && $cmsPage->canBeApproved()
            && $this->userOwnsContentSite($user, $cmsPage->site_id);
    }

    /**
     * Determine if the user can submit the page for review
     */
    public function submitForReview(Authenticatable $user, CmsPage $cmsPage): bool
    {
        return $user->can('SubmitForReview:CmsPage')
            && $cmsPage->canSubmitForReview()
            && $this->userOwnsContentSite($user, $cmsPage->site_id);
    }

    /**
     * Determine if the user can view revisions
     */
    public function viewRevisions(Authenticatable $user, CmsPage $cmsPage): bool
    {
        return $user->can('ViewRevisions:CmsPage')
            && $this->userOwnsContentSite($user, $cmsPage->site_id);
    }

    /**
     * Determine if the user can restore a revision
     */
    public function restoreRevision(Authenticatable $user, CmsPage $cmsPage): bool
    {
        return $user->can('RestoreRevision:CmsPage')
            && $this->userOwnsContentSite($user, $cmsPage->site_id);
    }

    /**
     * Determine if the user can generate preview links
     */
    public function generatePreviewLink(Authenticatable $user, CmsPage $cmsPage): bool
    {
        return $user->can('GeneratePreviewLink:CmsPage')
            && $this->userOwnsContentSite($user, $cmsPage->site_id);
    }
}
