<?php

declare(strict_types=1);

namespace TallCms\Cms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Core Site model.
 *
 * Every TallCMS installation has at least one Site record.
 * Standalone = one site. Multisite = many sites.
 * The multisite plugin extends this model with ownership,
 * domain verification, and multi-tenant features.
 */
class Site extends Model
{
    protected $table = 'tallcms_sites';

    protected $fillable = [
        'name',
        'domain',
        'theme',
        'locale',
        'uuid',
        'is_default',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (self $site) {
            if (empty($site->uuid)) {
                $site->uuid = (string) Str::uuid();
            }

            $site->domain = static::normalizeDomain($site->domain);

            // Ensure only one default site
            if ($site->is_default) {
                static::where('is_default', true)->update(['is_default' => false]);
            }
        });

        static::updating(function (self $site) {
            if ($site->isDirty('domain')) {
                $site->domain = static::normalizeDomain($site->domain);
            }

            if ($site->is_default && $site->isDirty('is_default')) {
                static::where('id', '!=', $site->id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }
        });
    }

    /**
     * Normalize a domain for storage and lookup.
     */
    public static function normalizeDomain(string $domain): string
    {
        $domain = strtolower(trim($domain));
        $domain = preg_replace('#^https?://#', '', $domain);
        $domain = rtrim($domain, '/');
        $domain = preg_replace('#:\d+$#', '', $domain);

        return $domain;
    }

    /**
     * Find an active site by domain.
     */
    public static function findByDomain(string $domain): ?self
    {
        return static::where('domain', static::normalizeDomain($domain))
            ->where('is_active', true)
            ->first();
    }

    /**
     * Get the default site.
     */
    public static function getDefault(): ?self
    {
        return static::where('is_default', true)->first();
    }

    public function settingOverrides(): HasMany
    {
        return $this->hasMany(SiteSettingOverride::class, 'site_id');
    }

    public function pages(): HasMany
    {
        return $this->hasMany(CmsPage::class, 'site_id');
    }

    public function menus(): HasMany
    {
        return $this->hasMany(TallcmsMenu::class, 'site_id');
    }
}
