<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Tallcms\Marketplace\Models\MarketplaceItem;
use Illuminate\Auth\Access\HandlesAuthorization;

class MarketplaceItemPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:MarketplaceItem');
    }

    public function view(AuthUser $authUser, MarketplaceItem $marketplaceItem): bool
    {
        return $authUser->can('View:MarketplaceItem');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:MarketplaceItem');
    }

    public function update(AuthUser $authUser, MarketplaceItem $marketplaceItem): bool
    {
        return $authUser->can('Update:MarketplaceItem');
    }

    public function delete(AuthUser $authUser, MarketplaceItem $marketplaceItem): bool
    {
        return $authUser->can('Delete:MarketplaceItem');
    }

    public function restore(AuthUser $authUser, MarketplaceItem $marketplaceItem): bool
    {
        return $authUser->can('Restore:MarketplaceItem');
    }

    public function forceDelete(AuthUser $authUser, MarketplaceItem $marketplaceItem): bool
    {
        return $authUser->can('ForceDelete:MarketplaceItem');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:MarketplaceItem');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:MarketplaceItem');
    }

    public function replicate(AuthUser $authUser, MarketplaceItem $marketplaceItem): bool
    {
        return $authUser->can('Replicate:MarketplaceItem');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:MarketplaceItem');
    }

}