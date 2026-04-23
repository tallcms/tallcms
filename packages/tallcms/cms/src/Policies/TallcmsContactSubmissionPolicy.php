<?php

declare(strict_types=1);

namespace TallCms\Cms\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Contracts\Auth\Authenticatable;
use TallCms\Cms\Models\TallcmsContactSubmission;
use TallCms\Cms\Policies\Concerns\ChecksSiteOwnership;

/**
 * TallcmsContactSubmission authorization.
 *
 * Shield controls role-level access; record-scoped methods layer a
 * site-ownership check so a site_owner only sees their own site's form
 * submissions. Single-site installs bypass the ownership check via the
 * trait's multisiteScopingActive() gate.
 */
class TallcmsContactSubmissionPolicy
{
    use ChecksSiteOwnership, HandlesAuthorization;

    public function viewAny(Authenticatable $user): bool
    {
        return $user->can('ViewAny:TallcmsContactSubmission');
    }

    public function view(Authenticatable $user, TallcmsContactSubmission $tallcmsContactSubmission): bool
    {
        return $user->can('View:TallcmsContactSubmission')
            && $this->userOwnsContentSite($user, $tallcmsContactSubmission->site_id);
    }

    public function create(Authenticatable $user): bool
    {
        return $user->can('Create:TallcmsContactSubmission');
    }

    public function update(Authenticatable $user, TallcmsContactSubmission $tallcmsContactSubmission): bool
    {
        return $user->can('Update:TallcmsContactSubmission')
            && $this->userOwnsContentSite($user, $tallcmsContactSubmission->site_id);
    }

    public function delete(Authenticatable $user, TallcmsContactSubmission $tallcmsContactSubmission): bool
    {
        return $user->can('Delete:TallcmsContactSubmission')
            && $this->userOwnsContentSite($user, $tallcmsContactSubmission->site_id);
    }

    public function restore(Authenticatable $user, TallcmsContactSubmission $tallcmsContactSubmission): bool
    {
        return $user->can('Restore:TallcmsContactSubmission')
            && $this->userOwnsContentSite($user, $tallcmsContactSubmission->site_id);
    }

    public function forceDelete(Authenticatable $user, TallcmsContactSubmission $tallcmsContactSubmission): bool
    {
        return $user->can('ForceDelete:TallcmsContactSubmission')
            && $this->userOwnsContentSite($user, $tallcmsContactSubmission->site_id);
    }

    public function forceDeleteAny(Authenticatable $user): bool
    {
        return $user->can('ForceDeleteAny:TallcmsContactSubmission');
    }

    public function restoreAny(Authenticatable $user): bool
    {
        return $user->can('RestoreAny:TallcmsContactSubmission');
    }

    public function replicate(Authenticatable $user, TallcmsContactSubmission $tallcmsContactSubmission): bool
    {
        return $user->can('Replicate:TallcmsContactSubmission')
            && $this->userOwnsContentSite($user, $tallcmsContactSubmission->site_id);
    }

    public function reorder(Authenticatable $user): bool
    {
        return $user->can('Reorder:TallcmsContactSubmission');
    }
}
