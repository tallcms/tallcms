<?php

declare(strict_types=1);

namespace TallCms\Cms\Rules;

use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

/**
 * Site-scoped unique validation helper.
 *
 * Wraps Rule::unique() and conditionally scopes to the current site_id
 * when the multisite plugin is active (i.e. the table has a site_id column).
 * Falls back to global uniqueness when multisite is not installed.
 */
class SiteAwareUnique
{
    /**
     * Create a site-aware unique validation rule.
     */
    public static function rule(string $table, string $column, ?int $ignoreId = null): Unique
    {
        $rule = Rule::unique($table, $column);

        if ($ignoreId) {
            $rule->ignore($ignoreId);
        }

        if (Schema::hasColumn($table, 'site_id')) {
            $siteId = static::resolveCurrentSiteId();
            if ($siteId) {
                $rule->where('site_id', $siteId);
            }
        }

        return $rule;
    }

    /**
     * Resolve the current site ID using the same two-tier strategy
     * as UniqueTranslatableSlug: admin session first, resolver second.
     */
    protected static function resolveCurrentSiteId(): ?int
    {
        // Tier 1: Admin session (always available during Filament/Livewire requests)
        $sessionValue = session('multisite_admin_site_id');
        if ($sessionValue && $sessionValue !== '__all_sites__' && is_numeric($sessionValue)) {
            return (int) $sessionValue;
        }

        // Tier 2: Resolver (frontend domain-based)
        if (app()->bound('tallcms.multisite.resolver')) {
            try {
                $resolver = app('tallcms.multisite.resolver');
                if ($resolver->isResolved() && $resolver->id()) {
                    return $resolver->id();
                }
            } catch (\Throwable) {
                // Multisite not functional
            }
        }

        return null;
    }
}
