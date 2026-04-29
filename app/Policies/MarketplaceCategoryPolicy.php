<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Tallcms\Marketplace\Models\MarketplaceCategory;
use Illuminate\Auth\Access\HandlesAuthorization;

class MarketplaceCategoryPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:MarketplaceCategory');
    }

    public function view(AuthUser $authUser, MarketplaceCategory $marketplaceCategory): bool
    {
        return $authUser->can('View:MarketplaceCategory');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:MarketplaceCategory');
    }

    public function update(AuthUser $authUser, MarketplaceCategory $marketplaceCategory): bool
    {
        return $authUser->can('Update:MarketplaceCategory');
    }

    public function delete(AuthUser $authUser, MarketplaceCategory $marketplaceCategory): bool
    {
        return $authUser->can('Delete:MarketplaceCategory');
    }

    public function restore(AuthUser $authUser, MarketplaceCategory $marketplaceCategory): bool
    {
        return $authUser->can('Restore:MarketplaceCategory');
    }

    public function forceDelete(AuthUser $authUser, MarketplaceCategory $marketplaceCategory): bool
    {
        return $authUser->can('ForceDelete:MarketplaceCategory');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:MarketplaceCategory');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:MarketplaceCategory');
    }

    public function replicate(AuthUser $authUser, MarketplaceCategory $marketplaceCategory): bool
    {
        return $authUser->can('Replicate:MarketplaceCategory');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:MarketplaceCategory');
    }

}