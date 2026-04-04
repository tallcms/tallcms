<?php

declare(strict_types=1);

namespace TallCms\Cms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SiteSetting extends Model
{
    protected $table = 'tallcms_site_settings';

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'description',
    ];

    protected $casts = [
        'value' => 'string',
    ];

    /**
     * Settings that are always installation-global, never per-site.
     * All other settings default to site_override behavior
     * (global default with optional per-site override).
     */
    protected static array $globalOnlyKeys = [
        // i18n: routes are registered globally at boot from config, not per-request.
        // Per-site override would split route structure vs runtime behavior.
        'i18n_enabled',
        'default_locale',
        'hide_default_locale',
        'i18n_locale_overrides',
        // Audit metadata: operational state, not user-facing config.
        'code_head_audit',
        'code_body_start_audit',
        'code_body_end_audit',
    ];

    /**
     * Check if a setting key is global-only (never per-site).
     */
    public static function isGlobalOnly(string $key): bool
    {
        return in_array($key, static::$globalOnlyKeys, true);
    }

    /**
     * Get a setting value by key.
     *
     * Policy-aware:
     * - Global-only keys always read from the global table.
     * - Site-override keys check per-site override first, fall back to global.
     *
     * Gracefully handles missing database table (e.g., during migrations or tests).
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        // Global-only keys skip the override check entirely
        if (static::isGlobalOnly($key)) {
            return static::getGlobal($key, $default);
        }

        // Site-override: check per-site override when a site is active
        $siteId = static::resolveCurrentSiteId();
        if ($siteId) {
            $siteCacheKey = "site_setting_{$siteId}_{$key}";

            $override = Cache::remember($siteCacheKey, 3600, function () use ($siteId, $key) {
                try {
                    return DB::table('tallcms_site_setting_overrides')
                        ->where('site_id', $siteId)
                        ->where('key', $key)
                        ->first();
                } catch (QueryException) {
                    return null;
                }
            });

            if ($override) {
                return static::castOverrideValue($override->value, $override->type);
            }
        }

        // Global fallback
        return static::getGlobal($key, $default);
    }

    /**
     * Get a setting value by key, always from the global table.
     *
     * Bypasses multisite site-specific overrides. Use this when the caller
     * must read the installation-wide value regardless of site context.
     */
    public static function getGlobal(string $key, mixed $default = null): mixed
    {
        $cacheKey = "site_setting_{$key}";

        return Cache::remember($cacheKey, 3600, function () use ($key, $default) {
            try {
                $setting = static::where('key', $key)->first();

                if (! $setting) {
                    return $default;
                }

                return match ($setting->type) {
                    'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
                    'json' => json_decode($setting->value, true),
                    'file' => $setting->value,
                    default => $setting->value,
                };
            } catch (QueryException) {
                return $default;
            }
        });
    }

    /**
     * Resolve the current site ID for multisite operations.
     *
     * Context-aware: uses different sources based on request type.
     * - Admin requests (tallcms.admin_context attribute): session is authoritative
     * - Frontend requests: resolver singleton is authoritative (domain-based)
     * - Boot/console (no request): returns null (global settings)
     *
     * This separation prevents admin session state from leaking into frontend
     * settings reads (e.g., visiting tallcms.test after selecting portal.test
     * in the admin switcher).
     */
    protected static function resolveCurrentSiteId(): ?int
    {
        $isAdminContext = request()?->attributes->get('tallcms.admin_context', false);

        if ($isAdminContext) {
            // Admin: session is the source of truth (immune to stale resolver)
            $sessionValue = session('multisite_admin_site_id');
            if ($sessionValue && $sessionValue !== '__all_sites__' && is_numeric($sessionValue)) {
                return (int) $sessionValue;
            }

            return null; // "All Sites" or no selection
        }

        // Frontend / non-admin: resolver is the source of truth (domain-based)
        if (app()->bound('tallcms.multisite.resolver')) {
            try {
                $resolver = app('tallcms.multisite.resolver');
                if ($resolver->isResolved() && $resolver->id()) {
                    return $resolver->id();
                }
            } catch (\Throwable) {
                // Resolver not functional
            }
        }

        return null;
    }

    /**
     * Cast a site setting override value based on its type.
     */
    protected static function castOverrideValue(mixed $value, string $type): mixed
    {
        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($value, true),
            'file' => $value,
            default => $value,
        };
    }

    /**
     * Set a setting value.
     *
     * Policy-aware:
     * - Global-only keys always write to the global table, even in site context.
     * - Site-override keys write to the per-site override table when a site is
     *   selected, or to the global table in "All Sites" / no-multisite mode.
     */
    public static function set(string $key, mixed $value, string $type = 'text', string $group = 'general', ?string $description = null): void
    {
        // Global-only keys always write to global table
        if (static::isGlobalOnly($key)) {
            static::setGlobal($key, $value, $type, $group, $description);

            return;
        }

        $processedValue = match ($type) {
            'boolean' => $value ? '1' : '0',
            'json' => json_encode($value),
            default => (string) $value,
        };

        // Site-override: write to per-site override when a site is active
        $siteId = static::resolveCurrentSiteId();
        if ($siteId) {
            try {
                DB::table('tallcms_site_setting_overrides')->updateOrInsert(
                    ['site_id' => $siteId, 'key' => $key],
                    [
                        'value' => $processedValue,
                        'type' => $type,
                        'updated_at' => now(),
                    ]
                );
                Cache::forget("site_setting_{$siteId}_{$key}");

                return;
            } catch (\Throwable) {
                // Override table not available — fall through to global write
            }
        }

        // Global write fallback
        static::setGlobal($key, $value, $type, $group, $description);
    }

    /**
     * Write a setting to the global table, bypassing multisite overrides.
     */
    public static function setGlobal(string $key, mixed $value, string $type = 'text', string $group = 'general', ?string $description = null): void
    {
        $processedValue = match ($type) {
            'boolean' => $value ? '1' : '0',
            'json' => json_encode($value),
            default => (string) $value,
        };

        static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $processedValue,
                'type' => $type,
                'group' => $group,
                'description' => $description,
            ]
        );

        Cache::forget("site_setting_{$key}");
    }

    /**
     * Remove a per-site override, causing the setting to inherit the global default.
     *
     * This is an explicit "reset to global" action — distinct from storing an empty
     * value (which is a valid override meaning "this site wants blank").
     */
    public static function resetToGlobal(string $key): void
    {
        $siteId = static::resolveCurrentSiteId();
        if (! $siteId) {
            return;
        }

        try {
            DB::table('tallcms_site_setting_overrides')
                ->where('site_id', $siteId)
                ->where('key', $key)
                ->delete();
            Cache::forget("site_setting_{$siteId}_{$key}");
        } catch (\Throwable) {
            // Ignore — table may not exist
        }
    }

    /**
     * Get all settings for a group.
     *
     * Gracefully handles missing database table.
     */
    public static function group(string $group): array
    {
        try {
            $settings = static::where('group', $group)->get();

            $result = [];
            foreach ($settings as $setting) {
                $result[$setting->key] = static::get($setting->key);
            }

            return $result;
        } catch (QueryException) {
            return [];
        }
    }

    /**
     * Clear all settings cache.
     *
     * Gracefully handles missing database table.
     */
    public static function clearCache(): void
    {
        try {
            $settings = static::all();
            foreach ($settings as $setting) {
                Cache::forget("site_setting_{$setting->key}");
            }

            // Also clear site-specific override caches if multisite is active
            $siteId = static::resolveCurrentSiteId();
            if ($siteId) {
                foreach ($settings as $setting) {
                    Cache::forget("site_setting_{$siteId}_{$setting->key}");
                }
            }
        } catch (QueryException) {
            // Table doesn't exist yet
        }
    }
}
