<?php

declare(strict_types=1);

namespace TallCms\Cms\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use TallCms\Cms\Models\SiteSettingOverride;

/**
 * Explicit-by-site-id settings service.
 *
 * All methods take an explicit $siteId — no session, no resolver,
 * no ambient context. This is the canonical API for admin settings writes.
 *
 * Frontend reads still go through SiteSetting::get() which resolves
 * from domain/session context.
 */
class SiteSettingsService
{
    /**
     * Get a setting value for a specific site.
     * Falls back to global if no override exists.
     */
    public function getForSite(int $siteId, string $key, mixed $default = null): mixed
    {
        $override = DB::table('tallcms_site_setting_overrides')
            ->where('site_id', $siteId)
            ->where('key', $key)
            ->first();

        if ($override) {
            return SiteSettingOverride::castRawValue($override->value, $override->type);
        }

        return $this->getGlobal($key, $default);
    }

    /**
     * Set a setting value for a specific site.
     */
    public function setForSite(int $siteId, string $key, mixed $value, string $type = 'text'): void
    {
        $processedValue = match ($type) {
            'boolean' => $value ? '1' : '0',
            'json' => json_encode($value),
            default => (string) ($value ?? ''),
        };

        DB::table('tallcms_site_setting_overrides')->updateOrInsert(
            ['site_id' => $siteId, 'key' => $key],
            [
                'value' => $processedValue,
                'type' => $type,
                'updated_at' => now(),
            ]
        );

        Cache::forget("site_setting_{$siteId}_{$key}");
    }

    /**
     * Remove a site-specific override. The site will inherit the global value.
     */
    public function resetForSite(int $siteId, string $key): void
    {
        DB::table('tallcms_site_setting_overrides')
            ->where('site_id', $siteId)
            ->where('key', $key)
            ->delete();

        Cache::forget("site_setting_{$siteId}_{$key}");
    }

    /**
     * Check if a site has an override for a specific key.
     */
    public function hasOverride(int $siteId, string $key): bool
    {
        return DB::table('tallcms_site_setting_overrides')
            ->where('site_id', $siteId)
            ->where('key', $key)
            ->exists();
    }

    /**
     * Get all overridden keys for a site.
     */
    public function getOverriddenKeys(int $siteId): array
    {
        return DB::table('tallcms_site_setting_overrides')
            ->where('site_id', $siteId)
            ->pluck('key')
            ->toArray();
    }

    /**
     * Get a global setting value (not site-specific).
     */
    public function getGlobal(string $key, mixed $default = null): mixed
    {
        try {
            $setting = DB::table('tallcms_site_settings')
                ->where('key', $key)
                ->first();

            if (! $setting) {
                return $default;
            }

            return match ($setting->type) {
                'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
                'json' => json_decode($setting->value, true),
                'file' => $setting->value,
                default => $setting->value,
            };
        } catch (\Throwable) {
            return $default;
        }
    }
}
