<?php

declare(strict_types=1);

namespace Tallcms\Multisite\Services;

use Illuminate\Http\Request;
use Tallcms\Multisite\Models\Site;

class CurrentSiteResolver
{
    /**
     * Session sentinel meaning "admin explicitly chose All Sites".
     * Distinguishes from null (no selection yet → fall back to default).
     */
    public const ALL_SITES_SENTINEL = '__all_sites__';

    protected ?Site $resolvedSite = null;

    protected bool $resolved = false;

    /**
     * Whether the admin explicitly selected "All Sites" mode.
     */
    protected bool $allSitesMode = false;

    /**
     * Resolve the current site from the request.
     */
    public function resolve(Request $request): void
    {
        if ($this->resolved) {
            return;
        }

        $this->resolved = true;

        // Admin context: use session-based site selection.
        // Detect admin by panel path or Livewire internal routes (which don't match /admin/*)
        $panelPath = config('tallcms.filament.panel_path', 'admin');
        $isAdminPath = $request->is("{$panelPath}*") || $request->is("{$panelPath}");
        $isLivewireRoute = $request->is('livewire/*');

        if ($isAdminPath || ($isLivewireRoute && session()->has('multisite_admin_site_id'))) {
            $this->resolveForAdmin();

            return;
        }

        // Frontend: domain-based resolution
        $this->resolveForFrontend($request);
    }

    protected function resolveForAdmin(): void
    {
        $sessionValue = session('multisite_admin_site_id');

        // Explicit "All Sites" selection
        if ($sessionValue === self::ALL_SITES_SENTINEL) {
            $this->allSitesMode = true;
            $this->resolvedSite = null;

            return;
        }

        // Specific site selected
        if ($sessionValue) {
            $this->resolvedSite = Site::where('id', $sessionValue)->where('is_active', true)->first();
        }

        // Fall back to default site for admin (first visit, no selection yet)
        $this->resolvedSite ??= Site::getDefault();
    }

    /**
     * Whether the admin is in "All Sites" mode.
     */
    public function isAllSitesMode(): bool
    {
        return $this->allSitesMode;
    }

    protected function resolveForFrontend(Request $request): void
    {
        $host = $request->getHost();
        $this->resolvedSite = Site::findByDomain($host);

        // If no site matches, resolvedSite stays null.
        // The middleware handles the 404 for frontend page requests.
    }

    /**
     * Get the resolved site.
     */
    public function get(): ?Site
    {
        return $this->resolvedSite;
    }

    /**
     * Get the resolved site ID.
     */
    public function id(): ?int
    {
        return $this->resolvedSite?->id;
    }

    /**
     * Whether the resolver has run and produced a result.
     */
    public function isResolved(): bool
    {
        return $this->resolved;
    }

    /**
     * Set the admin site (called by site switcher).
     * Pass null for "All Sites" mode.
     */
    public function setAdminSite(?int $siteId): void
    {
        if ($siteId === null) {
            // Explicit "All Sites" selection
            session(['multisite_admin_site_id' => self::ALL_SITES_SENTINEL]);
            $this->allSitesMode = true;
            $this->resolvedSite = null;
        } else {
            session(['multisite_admin_site_id' => $siteId]);
            $this->allSitesMode = false;
            $this->resolvedSite = Site::find($siteId);
        }
    }

    /**
     * Reset resolver state (useful for testing).
     */
    public function reset(): void
    {
        $this->resolvedSite = null;
        $this->resolved = false;
        $this->allSitesMode = false;
    }
}
