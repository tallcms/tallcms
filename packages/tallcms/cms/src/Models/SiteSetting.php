<?php

declare(strict_types=1);

namespace TallCms\Cms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SiteSetting extends Model
{
    protected const CACHE_PAYLOAD_MARKER = '__tallcms_site_setting_cache';

    protected const GLOBAL_SETTINGS_MEMO_ATTRIBUTE = 'tallcms.site_setting_globals';

    protected const SITE_OVERRIDES_MEMO_ATTRIBUTE = 'tallcms.site_setting_overrides';

    protected const CURRENT_SITE_ID_MEMOIZED_ATTRIBUTE = 'tallcms.current_site_id_memoized';

    protected const CURRENT_SITE_ID_ATTRIBUTE = 'tallcms.current_site_id';

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
    ];

    /**
     * Prefixes that are always installation-global.
     */
    protected static array $globalOnlyPrefixes = [
        'seo_',
    ];

    /**
     * Check if a setting key is global-only (never per-site).
     */
    public static function isGlobalOnly(string $key): bool
    {
        if (in_array($key, static::$globalOnlyKeys, true)) {
            return true;
        }

        foreach (static::$globalOnlyPrefixes as $prefix) {
            if (str_starts_with($key, $prefix)) {
                return true;
            }
        }

        return false;
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
        // Alias: site_name → resolve from Site.name model field
        if ($key === 'site_name') {
            return static::resolveSiteName($default);
        }

        // Global-only keys skip the override check entirely
        if (static::isGlobalOnly($key)) {
            return static::getGlobal($key, $default);
        }

        // Site-override: check per-site override when a site is active
        $siteId = static::resolveCurrentSiteId();
        if ($siteId) {
            $siteCacheKey = "site_setting_{$siteId}_{$key}";

            $override = static::memoizedSettingPayload(
                static::SITE_OVERRIDES_MEMO_ATTRIBUTE,
                $siteCacheKey,
                fn (): mixed => Cache::remember($siteCacheKey, 3600, function () use ($siteId, $key) {
                    try {
                        $override = DB::table('tallcms_site_setting_overrides')
                            ->where('site_id', $siteId)
                            ->where('key', $key)
                            ->first();

                        if (!$override) {
                            return static::missingCachePayload();
                        }

                        return static::cachePayload([
                            'value' => $override->value,
                            'type' => $override->type,
                        ]);
                    } catch (QueryException) {
                        return static::missingCachePayload();
                    }
                })
            );

            if (static::isCachePayload($override)) {
                if ($override['exists'] ?? false) {
                    return static::castOverrideValue($override['value'], $override['type']);
                }
            } elseif ($override) {
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

        $setting = static::memoizedSettingPayload(
            static::GLOBAL_SETTINGS_MEMO_ATTRIBUTE,
            $cacheKey,
            fn (): mixed => Cache::remember($cacheKey, 3600, function () use ($key) {
                try {
                    $setting = static::where('key', $key)->first();

                    if (!$setting) {
                        return static::missingCachePayload();
                    }

                    return static::cachePayload([
                        'value' => match ($setting->type) {
                            'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
                            'json' => json_decode($setting->value, true),
                            'file' => $setting->value,
                            default => $setting->value,
                        },
                    ]);
                } catch (QueryException) {
                    return static::missingCachePayload();
                }
            })
        );

        if (static::isCachePayload($setting)) {
            return ($setting['exists'] ?? false) ? $setting['value'] : $default;
        }

        return $setting;
    }

    /**
     * Cache payload wrapper used so missing/null settings are cached as real values.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected static function cachePayload(array $data): array
    {
        return [
            static::CACHE_PAYLOAD_MARKER => true,
            'exists' => true,
            ...$data,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected static function missingCachePayload(): array
    {
        return [
            static::CACHE_PAYLOAD_MARKER => true,
            'exists' => false,
        ];
    }

    protected static function isCachePayload(mixed $value): bool
    {
        return is_array($value) && ($value[static::CACHE_PAYLOAD_MARKER] ?? false) === true;
    }

    protected static function memoizedSettingPayload(string $attribute, string $key, callable $resolver): mixed
    {
        $request = request();
        $memoizedSettings = $request->attributes->get($attribute, []);

        if (array_key_exists($key, $memoizedSettings)) {
            return $memoizedSettings[$key];
        }

        $memoizedSettings[$key] = $resolver();
        $request->attributes->set($attribute, $memoizedSettings);

        return $memoizedSettings[$key];
    }

    /**
     * Resolve the current site ID for multisite operations.
     *
     * Context-aware: uses different sources based on request type.
     * - Admin requests (tallcms.admin_context attribute): session is authoritative
     * - Frontend requests: resolver singleton is authoritative (domain-based)
     * - Single-site installs (no multisite resolver bound): default site is current
     *
     * The default-site fallback closes a gap in single-site mode where the
     * Site edit page writes overrides keyed on the default site's id, but the
     * frontend reader has no resolver to map "current request" back to that
     * site id. Without the fallback, site-scoped settings saved in the admin
     * never surface on the frontend.
     */
    protected static function resolveCurrentSiteId(): ?int
    {
        $request = request();

        if ($request->attributes->get(static::CURRENT_SITE_ID_MEMOIZED_ATTRIBUTE, false)) {
            return $request->attributes->get(static::CURRENT_SITE_ID_ATTRIBUTE);
        }

        $siteId = null;
        $isAdminContext = request()?->attributes->get('tallcms.admin_context', false);

        if ($isAdminContext) {
            // Admin: session is the source of truth (immune to stale resolver)
            $sessionValue = session('multisite_admin_site_id');
            if ($sessionValue && $sessionValue !== '__all_sites__' && is_numeric($sessionValue)) {
                $siteId = (int) $sessionValue;
            }
        } elseif (app()->bound('tallcms.multisite.resolver')) {
            // Frontend / non-admin with multisite plugin: resolver is authoritative (domain-based)
            try {
                $resolver = app('tallcms.multisite.resolver');
                if ($resolver->isResolved() && $resolver->id()) {
                    $siteId = $resolver->id();
                }
            } catch (\Throwable) {
                // Resolver not functional
            }
        } else {
            // Single-site install: default site is always the current site.
            $siteId = static::defaultSiteId();
        }

        $request->attributes->set(static::CURRENT_SITE_ID_ATTRIBUTE, $siteId);
        $request->attributes->set(static::CURRENT_SITE_ID_MEMOIZED_ATTRIBUTE, true);

        return $siteId;
    }

    /**
     * Resolve the default site's id, memoized per request.
     *
     * Used as the last-resort fallback for resolveCurrentSiteId() when no
     * multisite resolver is bound (i.e., the multisite plugin is not installed).
     */
    protected static ?int $memoizedDefaultSiteId = null;

    protected static ?string $memoizedDefaultSiteName = null;

    protected static bool $defaultSiteIdMemoized = false;

    protected static function defaultSiteId(): ?int
    {
        if (static::$defaultSiteIdMemoized) {
            return static::$memoizedDefaultSiteId;
        }

        try {
            $site = DB::table('tallcms_sites')
                ->where('is_default', true)
                ->select(['id', 'name'])
                ->first() ?? DB::table('tallcms_sites')
                ->orderBy('id')
                ->select(['id', 'name'])
                ->first();

            static::$memoizedDefaultSiteId = $site?->id ? (int) $site->id : null;
            static::$memoizedDefaultSiteName = is_string($site?->name) && $site->name !== '' ? $site->name : null;
        } catch (\Throwable) {
            static::$memoizedDefaultSiteId = null;
            static::$memoizedDefaultSiteName = null;
        }

        static::$defaultSiteIdMemoized = true;

        return static::$memoizedDefaultSiteId;
    }

    /**
     * Reset the memoized default-site id. Used by tests between site mutations.
     */
    public static function forgetMemoizedDefaultSiteId(): void
    {
        static::$memoizedDefaultSiteId = null;
        static::$memoizedDefaultSiteName = null;
        static::$defaultSiteIdMemoized = false;

        request()?->attributes->remove('tallcms.site_names');
        static::forgetRequestMemoizedSettings();
    }

    protected static function forgetRequestMemoizedSettings(): void
    {
        $request = request();

        $request->attributes->remove(static::GLOBAL_SETTINGS_MEMO_ATTRIBUTE);
        $request->attributes->remove(static::SITE_OVERRIDES_MEMO_ATTRIBUTE);
        $request->attributes->remove(static::CURRENT_SITE_ID_ATTRIBUTE);
        $request->attributes->remove(static::CURRENT_SITE_ID_MEMOIZED_ATTRIBUTE);
    }

    /**
     * Resolve site_name from the Site model's name field.
     *
     * Legacy read compatibility: SiteSetting::get('site_name') returns the
     * current site's name instead of looking up a setting override.
     * site_name is no longer a setting — it's a model field on Site.
     *
     * Fallback chain:
     * 1. Current site's name (via resolveCurrentSiteId)
     * 2. Global site_name setting row (preserves pre-migration behavior)
     * 3. Default site's name
     * 4. Caller-provided $default / config('app.name')
     */
    protected static function resolveSiteName(mixed $default = null): mixed
    {
        try {
            $siteId = static::resolveCurrentSiteId();

            if ($siteId) {
                $name = static::memoizedDefaultSiteName($siteId)
                    ?? static::memoizedSiteName("site:{$siteId}", fn (): ?string => DB::table('tallcms_sites')->where('id', $siteId)->value('name'));

                if ($name) {
                    return $name;
                }
            }

            // Fallback: global site_name setting (preserves old no-context behavior)
            $global = static::getGlobal('site_name');
            if ($global) {
                return $global;
            }

            // Fallback: default site's name
            $name = static::memoizedSiteName('default', fn (): ?string => DB::table('tallcms_sites')->where('is_default', true)->value('name'));
            if ($name) {
                return $name;
            }
        } catch (\Throwable) {
        }

        return $default ?? config('app.name', 'My Site');
    }

    protected static function memoizedDefaultSiteName(int $siteId): ?string
    {
        if (!static::$defaultSiteIdMemoized || static::$memoizedDefaultSiteId !== $siteId) {
            return null;
        }

        return static::$memoizedDefaultSiteName;
    }

    protected static function memoizedSiteName(string $key, callable $resolver): ?string
    {
        $request = request();
        $names = $request->attributes->get('tallcms.site_names', []);

        if (array_key_exists($key, $names)) {
            return $names[$key];
        }

        $name = $resolver();
        $names[$key] = is_string($name) && $name !== '' ? $name : null;
        $request->attributes->set('tallcms.site_names', $names);

        return $names[$key];
    }

    /**
     * Write site_name to the Site model's name field.
     *
     * Legacy write compatibility: SiteSetting::set('site_name', ...)
     * writes to tallcms_sites.name instead of the override table.
     * In site context: updates that site's name.
     * Without context: writes to global site_name setting (standalone/no session).
     */
    protected static function writeSiteName(string $value): void
    {
        try {
            $siteId = static::resolveCurrentSiteId();

            if ($siteId) {
                DB::table('tallcms_sites')
                    ->where('id', $siteId)
                    ->update(['name' => $value]);

                return;
            }

            // No site context: write to global setting (standalone behavior)
            static::setGlobal('site_name', $value, 'text', 'general');
        } catch (\Throwable) {
        }
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
        // Alias: site_name → write to Site.name model field
        if ($key === 'site_name') {
            static::writeSiteName((string) $value);

            return;
        }

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
                static::forgetRequestMemoizedSettings();

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
        static::forgetRequestMemoizedSettings();
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
        if (!$siteId) {
            return;
        }

        try {
            DB::table('tallcms_site_setting_overrides')
                ->where('site_id', $siteId)
                ->where('key', $key)
                ->delete();
            Cache::forget("site_setting_{$siteId}_{$key}");
            static::forgetRequestMemoizedSettings();
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
        static::forgetMemoizedDefaultSiteId();
        static::forgetRequestMemoizedSettings();

        try {
            $settings = static::all();
            foreach ($settings as $setting) {
                Cache::forget("site_setting_{$setting->key}");
            }

            // Clear site-specific override caches for every known site so the
            // frontend picks up changes regardless of which site is "current".
            try {
                $siteIds = DB::table('tallcms_sites')->pluck('id');
                foreach ($siteIds as $siteId) {
                    foreach ($settings as $setting) {
                        Cache::forget("site_setting_{$siteId}_{$setting->key}");
                    }
                }
            } catch (\Throwable) {
                // tallcms_sites table may not exist yet
            }
        } catch (QueryException) {
            // Table doesn't exist yet
        }
    }
}
