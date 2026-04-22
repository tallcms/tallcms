<?php

declare(strict_types=1);

namespace TallCms\Cms\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Contracts\Auth\Authenticatable;
use TallCms\Cms\Models\TallcmsMenu;
use TallCms\Cms\Policies\Concerns\ChecksSiteOwnership;

class TallcmsMenuPolicy
{
    use ChecksSiteOwnership;
    use HandlesAuthorization;

    public function viewAny(Authenticatable $user): bool
    {
        return $user->can('ViewAny:TallcmsMenu');
    }

    public function view(Authenticatable $user, TallcmsMenu $tallcmsMenu): bool
    {
        return $user->can('View:TallcmsMenu')
            && $this->userOwnsContentSite($user, $tallcmsMenu->site_id);
    }

    public function create(Authenticatable $user): bool
    {
        return $user->can('Create:TallcmsMenu');
    }

    public function update(Authenticatable $user, TallcmsMenu $tallcmsMenu): bool
    {
        return $user->can('Update:TallcmsMenu')
            && $this->userOwnsContentSite($user, $tallcmsMenu->site_id);
    }

    public function delete(Authenticatable $user, TallcmsMenu $tallcmsMenu): bool
    {
        return $user->can('Delete:TallcmsMenu')
            && $this->userOwnsContentSite($user, $tallcmsMenu->site_id);
    }

    public function restore(Authenticatable $user, TallcmsMenu $tallcmsMenu): bool
    {
        return $user->can('Restore:TallcmsMenu')
            && $this->userOwnsContentSite($user, $tallcmsMenu->site_id);
    }

    public function forceDelete(Authenticatable $user, TallcmsMenu $tallcmsMenu): bool
    {
        return $user->can('ForceDelete:TallcmsMenu')
            && $this->userOwnsContentSite($user, $tallcmsMenu->site_id);
    }

    public function forceDeleteAny(Authenticatable $user): bool
    {
        return $user->can('ForceDeleteAny:TallcmsMenu');
    }

    public function restoreAny(Authenticatable $user): bool
    {
        return $user->can('RestoreAny:TallcmsMenu');
    }

    public function replicate(Authenticatable $user, TallcmsMenu $tallcmsMenu): bool
    {
        return $user->can('Replicate:TallcmsMenu')
            && $this->userOwnsContentSite($user, $tallcmsMenu->site_id);
    }

    public function reorder(Authenticatable $user): bool
    {
        return $user->can('Reorder:TallcmsMenu');
    }
}
