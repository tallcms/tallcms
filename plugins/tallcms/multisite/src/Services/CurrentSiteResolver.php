<?php

declare(strict_types=1);

namespace Tallcms\Multisite\Services;

use Illuminate\Http\Request;
use Tallcms\Multisite\Models\Site;

class CurrentSiteResolver
{
    protected ?Site $resolvedSite = null;

    protected bool $resolved = false;

    /**
     * Resolve the current site from the request.
     */
    public function resolve(Request $request): void
    {
        if ($this->resolved) {
            return;
        }

        $this->resolved = true;

        // Admin panel: use session-based site selection
        $panelPath = config('tallcms.filament.panel_path', 'admin');
        if ($request->is("{$panelPath}*") || $request->is("{$panelPath}")) {
            $this->resolveForAdmin();

            return;
        }

        // Frontend: domain-based resolution
        $this->resolveForFrontend($request);
    }

    protected function resolveForAdmin(): void
    {
        $siteId = session('multisite_admin_site_id');

        if ($siteId) {
            $this->resolvedSite = Site::where('id', $siteId)->where('is_active', true)->first();
        }

        // Fall back to default site for admin
        $this->resolvedSite ??= Site::getDefault();
    }

    protected function resolveForFrontend(Request $request): void
    {
        $host = $request->getHost();
        $this->resolvedSite = Site::findByDomain($host);

        if ($this->resolvedSite) {
            return;
        }

        // In local/testing: fall back to default site
        if (app()->environment('local', 'testing')) {
            $this->resolvedSite = Site::getDefault();

            return;
        }

        // In production: strict — 404 for unknown domains
        abort(404);
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
     */
    public function setAdminSite(?int $siteId): void
    {
        session(['multisite_admin_site_id' => $siteId]);

        if ($siteId) {
            $this->resolvedSite = Site::find($siteId);
        } else {
            $this->resolvedSite = null;
        }
    }

    /**
     * Reset resolver state (useful for testing).
     */
    public function reset(): void
    {
        $this->resolvedSite = null;
        $this->resolved = false;
    }
}
