<?php

declare(strict_types=1);

namespace TallCms\Cms\Contracts;

/**
 * Contract for User models that work with TallCMS.
 *
 * This interface defines the minimum requirements for a User model
 * to work with TallCMS's permission and role system. It's compatible
 * with Spatie's laravel-permission package.
 *
 * Implementation is optional but recommended for plugin mode users
 * who want to validate their User model meets TallCMS requirements.
 *
 * @example
 * ```php
 * class User extends Authenticatable implements TallCmsUserContract
 * {
 *     use HasRoles; // From Spatie\Permission\Traits\HasRoles
 *     // ...
 * }
 * ```
 */
interface TallCmsUserContract
{
    /**
     * Check if the user has a specific role.
     *
     * @param  string|array<string>  $roles  Role name(s) to check
     * @return bool True if user has any of the specified roles
     */
    public function hasRole(string|array $roles): bool;

    /**
     * Check if the user has a specific permission.
     *
     * @param  string  $permission  Permission name to check
     * @return bool True if user has the permission
     */
    public function hasPermissionTo(string $permission): bool;

    /**
     * Get the user's display name.
     *
     * Returns the user's name for display in the CMS (e.g., author attribution).
     * Implementations should return a sensible default if name is not set.
     *
     * @return string The user's display name
     */
    public function getDisplayName(): string;

    /**
     * Get the user's email address.
     *
     * @return string The user's email address
     */
    public function getEmail(): string;
}
