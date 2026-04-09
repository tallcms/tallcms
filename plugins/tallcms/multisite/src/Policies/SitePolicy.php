<?php

declare(strict_types=1);

namespace Tallcms\Multisite\Policies;

use App\Models\User;
use Tallcms\Multisite\Models\Site;

/**
 * Authorization policy for Site resources.
 *
 * Enforces ownership: non-super-admins can only manage their own sites.
 * Site quotas and creation limits are the app layer's responsibility,
 * not the plugin's. Override this policy in your app if you need
 * subscription-based quotas (e.g., PropertyPages agent plan limits).
 */
class SitePolicy
{
    public function viewAny(User $user): bool
    {
        return true; // Filtered by SiteResource::getEloquentQuery()
    }

    public function view(User $user, Site $site): bool
    {
        return $user->hasRole('super_admin') || $site->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true; // App layer handles quotas (subscriptions, plan limits)
    }

    public function update(User $user, Site $site): bool
    {
        return $user->hasRole('super_admin') || $site->user_id === $user->id;
    }

    public function delete(User $user, Site $site): bool
    {
        return $user->hasRole('super_admin') || $site->user_id === $user->id;
    }
}
