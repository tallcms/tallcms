<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Tallcms\Marketplace\Models\MarketplaceSubmission;
use Illuminate\Auth\Access\HandlesAuthorization;

class MarketplaceSubmissionPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:MarketplaceSubmission');
    }

    public function view(AuthUser $authUser, MarketplaceSubmission $marketplaceSubmission): bool
    {
        return $authUser->can('View:MarketplaceSubmission');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:MarketplaceSubmission');
    }

    public function update(AuthUser $authUser, MarketplaceSubmission $marketplaceSubmission): bool
    {
        return $authUser->can('Update:MarketplaceSubmission');
    }

    public function delete(AuthUser $authUser, MarketplaceSubmission $marketplaceSubmission): bool
    {
        return $authUser->can('Delete:MarketplaceSubmission');
    }

    public function restore(AuthUser $authUser, MarketplaceSubmission $marketplaceSubmission): bool
    {
        return $authUser->can('Restore:MarketplaceSubmission');
    }

    public function forceDelete(AuthUser $authUser, MarketplaceSubmission $marketplaceSubmission): bool
    {
        return $authUser->can('ForceDelete:MarketplaceSubmission');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:MarketplaceSubmission');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:MarketplaceSubmission');
    }

    public function replicate(AuthUser $authUser, MarketplaceSubmission $marketplaceSubmission): bool
    {
        return $authUser->can('Replicate:MarketplaceSubmission');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:MarketplaceSubmission');
    }

}