<?php

declare(strict_types=1);

namespace TallCms\Cms\Services;

use Illuminate\Support\Collection;
use TallCms\Cms\Contracts\LocaleProviderInterface;
use TallCms\Cms\Models\SiteSetting;

class LocaleRegistry
{
    protected ?Collection $locales = null;

    protected array $providers = [];

    /**
     * Register a locale provider (called by language pack plugins).
     */
    public function registerProvider(LocaleProviderInterface $provider): void
    {
        $this->providers[] = $provider;
        $this->locales = null; // Clear cache
    }

    /**
     * Get all enabled locales (merged from config + plugins + DB).
     *
     * Merge rules:
     * 1. Config defines base locales
     * 2. Plugins can ADD new locale codes only (cannot override config)
     * 3. DB can MODIFY existing locales only (cannot add new codes)
     *
     * @return Collection<string, array{label: string, native: string, rtl: bool, enabled: bool, source: string}>
     */
    public function getLocales(): Collection
    {
        if ($this->locales !== null) {
            return $this->locales;
        }

        $locales = collect();

        // 1. Config (base) - normalized to Laravel format (underscores)
        foreach (config('tallcms.i18n.locales', []) as $code => $data) {
            $normalizedCode = self::normalizeLocaleCode($code);
            $locales[$normalizedCode] = $this->normalizeLocale($data, 'config');
        }

        // 2. Plugins (add NEW codes only - cannot override config)
        foreach ($this->providers as $provider) {
            foreach ($provider->getLocales() as $code => $data) {
                $normalizedCode = self::normalizeLocaleCode($code);
                if (! $locales->has($normalizedCode)) {
                    $locales[$normalizedCode] = $this->normalizeLocale($data, 'plugin');
                }
                // Silently ignore plugin attempts to override config locales
            }
        }

        // 3. DB (modify EXISTING codes only - cannot add new)
        $dbOverrides = SiteSetting::get('i18n_locale_overrides', []);
        foreach ($dbOverrides as $code => $overrides) {
            $normalizedCode = self::normalizeLocaleCode($code);
            if ($locales->has($normalizedCode)) {
                // Merge DB overrides into existing locale (preserves source tracking)
                $existing = $locales[$normalizedCode];
                $locales[$normalizedCode] = array_merge(
                    $existing,
                    array_filter($overrides, fn ($v) => $v !== null), // Only non-null overrides
                    ['source' => $existing['source'], 'modified_by' => 'db']
                );
            }
            // Silently ignore DB attempts to add new locale codes
        }

        // Filter to enabled only
        $this->locales = $locales->filter(fn ($locale) => $locale['enabled'] ?? true);

        return $this->locales;
    }

    /**
     * Get ALL locales including disabled (for admin UI).
     */
    public function getAllLocales(): Collection
    {
        // Temporarily bypass enabled filter
        $cached = $this->locales;
        $this->locales = null;

        $locales = collect();

        foreach (config('tallcms.i18n.locales', []) as $code => $data) {
            $normalizedCode = self::normalizeLocaleCode($code);
            $locales[$normalizedCode] = $this->normalizeLocale($data, 'config');
        }

        foreach ($this->providers as $provider) {
            foreach ($provider->getLocales() as $code => $data) {
                $normalizedCode = self::normalizeLocaleCode($code);
                if (! $locales->has($normalizedCode)) {
                    $locales[$normalizedCode] = $this->normalizeLocale($data, 'plugin');
                }
            }
        }

        $dbOverrides = SiteSetting::get('i18n_locale_overrides', []);
        foreach ($dbOverrides as $code => $overrides) {
            $normalizedCode = self::normalizeLocaleCode($code);
            if ($locales->has($normalizedCode)) {
                $existing = $locales[$normalizedCode];
                $locales[$normalizedCode] = array_merge(
                    $existing,
                    array_filter($overrides, fn ($v) => $v !== null),
                    ['source' => $existing['source'], 'modified_by' => 'db']
                );
            }
        }

        $this->locales = $cached;

        return $locales;
    }

    /**
     * Get locale codes only (for validation, Filament plugin config).
     */
    public function getLocaleCodes(): array
    {
        return $this->getLocales()->keys()->all();
    }

    /**
     * Get locales formatted for select options.
     */
    public function getLocaleOptions(): array
    {
        return $this->getLocales()
            ->mapWithKeys(fn ($data, $code) => [$code => $data['label']])
            ->all();
    }

    /**
     * Check if a locale code is registered and enabled.
     * Normalizes input to handle both es-MX and es_mx formats.
     */
    public function isValidLocale(string $code): bool
    {
        return $this->getLocales()->has(self::normalizeLocaleCode($code));
    }

    /**
     * Get the default locale (validated to exist in registry).
     * Uses tallcms_i18n_config() to check DB settings first.
     * Normalizes config value to handle both es-MX and es_mx formats.
     */
    public function getDefaultLocale(): string
    {
        $default = self::normalizeLocaleCode(tallcms_i18n_config('default_locale', 'en'));

        // Ensure default exists in registry
        if (! $this->isValidLocale($default)) {
            return $this->getLocales()->keys()->first() ?? 'en';
        }

        return $default;
    }

    /**
     * Get reserved slugs (locale codes that can't be used as page slugs).
     * Includes ALL registered locales (even disabled) in BOTH formats:
     * - Internal format: es_mx (for database storage checks)
     * - BCP-47 format: es-mx lowercase (for URL route collision prevention)
     */
    public function getReservedSlugs(): array
    {
        $locales = $this->getAllLocales()->keys()->all();
        $reserved = [];

        foreach ($locales as $code) {
            // Internal format (lowercase with underscores)
            $reserved[] = $code;
            // BCP-47 format (lowercase for comparison)
            $reserved[] = strtolower(self::toBcp47($code));
        }

        return array_unique($reserved);
    }

    /**
     * Normalize a locale code to Laravel format (lowercase with underscores).
     * Accepts: en-US, en_US, EN-us → en_us
     */
    public static function normalizeLocaleCode(string $code): string
    {
        // Convert hyphens to underscores, lowercase
        return strtolower(str_replace('-', '_', $code));
    }

    /**
     * Convert internal locale code to BCP-47 format for public output.
     * Internal format: es_mx (Laravel style, lowercase with underscores)
     * BCP-47 format: es-MX (hyphens, proper casing)
     *
     * Handles:
     * - Simple: en → en
     * - Region: es_mx → es-MX (region uppercase)
     * - Script: zh_hans → zh-Hans (script title case)
     * - Full: zh_hans_cn → zh-Hans-CN (script title, region upper)
     */
    public static function toBcp47(string $code): string
    {
        $parts = explode('_', $code);

        if (count($parts) === 1) {
            // Simple code like 'en' stays as-is
            return $parts[0];
        }

        if (count($parts) === 2) {
            // Could be language_region (es_mx) or language_script (zh_hans)
            // Script codes are 4 chars, region codes are 2-3 chars
            if (strlen($parts[1]) === 4) {
                // Script tag: zh_hans → zh-Hans (title case)
                return $parts[0].'-'.ucfirst($parts[1]);
            }
            // Region: es_mx → es-MX (uppercase)
            return $parts[0].'-'.strtoupper($parts[1]);
        }

        if (count($parts) === 3) {
            // Full: zh_hans_cn → zh-Hans-CN
            return $parts[0].'-'.ucfirst($parts[1]).'-'.strtoupper($parts[2]);
        }

        // Fallback: just replace underscores with hyphens
        return str_replace('_', '-', $code);
    }

    /**
     * Normalize locale data to consistent structure.
     */
    protected function normalizeLocale(array|string $data, string $source): array
    {
        if (is_string($data)) {
            $data = ['label' => $data];
        }

        return [
            'label' => $data['label'] ?? 'Unknown',
            'native' => $data['native'] ?? $data['label'] ?? 'Unknown',
            'rtl' => $data['rtl'] ?? false,
            'enabled' => $data['enabled'] ?? true,
            'source' => $source,
        ];
    }

    /**
     * Clear cached locales (call after DB changes).
     */
    public function clearCache(): void
    {
        $this->locales = null;
    }
}
