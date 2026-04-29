<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Tallcms\RedirectManager\Models\Redirect;
use Illuminate\Auth\Access\HandlesAuthorization;

class RedirectPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Redirect');
    }

    public function view(AuthUser $authUser, Redirect $redirect): bool
    {
        return $authUser->can('View:Redirect');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Redirect');
    }

    public function update(AuthUser $authUser, Redirect $redirect): bool
    {
        return $authUser->can('Update:Redirect');
    }

    public function delete(AuthUser $authUser, Redirect $redirect): bool
    {
        return $authUser->can('Delete:Redirect');
    }

    public function restore(AuthUser $authUser, Redirect $redirect): bool
    {
        return $authUser->can('Restore:Redirect');
    }

    public function forceDelete(AuthUser $authUser, Redirect $redirect): bool
    {
        return $authUser->can('ForceDelete:Redirect');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Redirect');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Redirect');
    }

    public function replicate(AuthUser $authUser, Redirect $redirect): bool
    {
        return $authUser->can('Replicate:Redirect');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Redirect');
    }

}