<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Tallcms\Multisite\Models\SiteTemplate;
use Illuminate\Auth\Access\HandlesAuthorization;

class SiteTemplatePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:SiteTemplate');
    }

    public function view(AuthUser $authUser, SiteTemplate $siteTemplate): bool
    {
        return $authUser->can('View:SiteTemplate');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:SiteTemplate');
    }

    public function update(AuthUser $authUser, SiteTemplate $siteTemplate): bool
    {
        return $authUser->can('Update:SiteTemplate');
    }

    public function delete(AuthUser $authUser, SiteTemplate $siteTemplate): bool
    {
        return $authUser->can('Delete:SiteTemplate');
    }

    public function restore(AuthUser $authUser, SiteTemplate $siteTemplate): bool
    {
        return $authUser->can('Restore:SiteTemplate');
    }

    public function forceDelete(AuthUser $authUser, SiteTemplate $siteTemplate): bool
    {
        return $authUser->can('ForceDelete:SiteTemplate');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:SiteTemplate');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:SiteTemplate');
    }

    public function replicate(AuthUser $authUser, SiteTemplate $siteTemplate): bool
    {
        return $authUser->can('Replicate:SiteTemplate');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:SiteTemplate');
    }

}