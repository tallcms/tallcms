<?php

declare(strict_types=1);

namespace Tallcms\Multisite\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Site extends Model
{
    protected $table = 'tallcms_sites';

    protected $fillable = [
        'name',
        'domain',
        'theme',
        'locale',
        'uuid',
        'user_id',
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
     *
     * Lowercases, strips protocol, trailing slash, and port.
     */
    public static function normalizeDomain(string $domain): string
    {
        $domain = strtolower(trim($domain));

        // Strip protocol
        $domain = preg_replace('#^https?://#', '', $domain);

        // Strip trailing slash
        $domain = rtrim($domain, '/');

        // Strip port
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
        return $this->hasMany(\TallCms\Cms\Models\CmsPage::class, 'site_id')
            ->withoutGlobalScopes();
    }

    public function menus(): HasMany
    {
        return $this->hasMany(\TallCms\Cms\Models\TallcmsMenu::class, 'site_id')
            ->withoutGlobalScopes();
    }
}
