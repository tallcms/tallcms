<?php

declare(strict_types=1);

namespace TallCms\Cms\Policies;

use TallCms\Cms\Models\TallcmsContactSubmission;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Contracts\Auth\Authenticatable;

class TallcmsContactSubmissionPolicy
{
    use HandlesAuthorization;

    public function viewAny(Authenticatable $user): bool
    {
        return $user->can('ViewAny:TallcmsContactSubmission');
    }

    public function view(Authenticatable $user, TallcmsContactSubmission $tallcmsContactSubmission): bool
    {
        return $user->can('View:TallcmsContactSubmission');
    }

    public function create(Authenticatable $user): bool
    {
        return $user->can('Create:TallcmsContactSubmission');
    }

    public function update(Authenticatable $user, TallcmsContactSubmission $tallcmsContactSubmission): bool
    {
        return $user->can('Update:TallcmsContactSubmission');
    }

    public function delete(Authenticatable $user, TallcmsContactSubmission $tallcmsContactSubmission): bool
    {
        return $user->can('Delete:TallcmsContactSubmission');
    }

    public function restore(Authenticatable $user, TallcmsContactSubmission $tallcmsContactSubmission): bool
    {
        return $user->can('Restore:TallcmsContactSubmission');
    }

    public function forceDelete(Authenticatable $user, TallcmsContactSubmission $tallcmsContactSubmission): bool
    {
        return $user->can('ForceDelete:TallcmsContactSubmission');
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
        return $user->can('Replicate:TallcmsContactSubmission');
    }

    public function reorder(Authenticatable $user): bool
    {
        return $user->can('Reorder:TallcmsContactSubmission');
    }
}
