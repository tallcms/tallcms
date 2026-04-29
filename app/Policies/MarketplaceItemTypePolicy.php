<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Tallcms\Marketplace\Models\MarketplaceItemType;
use Illuminate\Auth\Access\HandlesAuthorization;

class MarketplaceItemTypePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:MarketplaceItemType');
    }

    public function view(AuthUser $authUser, MarketplaceItemType $marketplaceItemType): bool
    {
        return $authUser->can('View:MarketplaceItemType');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:MarketplaceItemType');
    }

    public function update(AuthUser $authUser, MarketplaceItemType $marketplaceItemType): bool
    {
        return $authUser->can('Update:MarketplaceItemType');
    }

    public function delete(AuthUser $authUser, MarketplaceItemType $marketplaceItemType): bool
    {
        return $authUser->can('Delete:MarketplaceItemType');
    }

    public function restore(AuthUser $authUser, MarketplaceItemType $marketplaceItemType): bool
    {
        return $authUser->can('Restore:MarketplaceItemType');
    }

    public function forceDelete(AuthUser $authUser, MarketplaceItemType $marketplaceItemType): bool
    {
        return $authUser->can('ForceDelete:MarketplaceItemType');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:MarketplaceItemType');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:MarketplaceItemType');
    }

    public function replicate(AuthUser $authUser, MarketplaceItemType $marketplaceItemType): bool
    {
        return $authUser->can('Replicate:MarketplaceItemType');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:MarketplaceItemType');
    }

}