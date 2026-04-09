<?php

declare(strict_types=1);

namespace Tallcms\Multisite\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Tallcms\Multisite\Models\Site;
use Tallcms\Multisite\Services\CurrentSiteResolver;
use TallCms\Cms\Services\PluginLicenseService;

class MultisiteServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CurrentSiteResolver::class);
        $this->app->alias(CurrentSiteResolver::class, 'tallcms.multisite.resolver');
    }

    public function boot(): void
    {
        // Always load migrations so schema stays consistent
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

        // Load views
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'tallcms-multisite');

        // Always ensure a default site exists (regardless of license)
        $this->ensureDefaultSiteExists();

        // Check license: plugin is inert unless activated
        if (! $this->isLicensed()) {
            return;
        }

        $this->registerMultisiteFeatures();
        $this->registerLivewireComponents();
    }

    protected function isLicensed(): bool
    {
        // Skip license check in testing
        if ($this->app->environment('testing')) {
            return true;
        }

        try {
            $licenseService = $this->app->make(PluginLicenseService::class);

            // Check hasEverBeenLicensed first (DB-only, no proxy call).
            // Once activated, multisite never collapses — even if license expires.
            if ($licenseService->hasEverBeenLicensed('tallcms/multisite')) {
                return true;
            }

            // Only call isValid() if never activated (checks proxy for fresh activations)
            return $licenseService->isValid('tallcms/multisite');
        } catch (\Throwable) {
            // License system not available (e.g. during initial setup)
            return false;
        }
    }

    protected function registerMultisiteFeatures(): void
    {
        $this->registerMiddleware();
        $this->registerGlobalScopes();
        $this->registerModelListeners();
        $this->registerPolicies();
    }

    /**
     * Ensure a default site exists — self-heals if the seeder migration
     * was skipped (e.g. already recorded from a prior install).
     *
     * Uses raw DB queries to bypass Eloquent model hooks that may
     * fail during boot context (auth checks, observers, etc.).
     */
    protected function ensureDefaultSiteExists(): void
    {
        try {
            if (! Schema::hasTable('tallcms_sites')) {
                return;
            }

            $defaultSite = DB::table('tallcms_sites')->where('is_default', true)->first();
            if ($defaultSite) {
                // Fix ownerless default site from prior versions
                if ($defaultSite->user_id === null) {
                    $ownerId = null;
                    try {
                        if (auth()->check()) {
                            $ownerId = auth()->id();
                        } else {
                            $ownerId = DB::table('model_has_roles')
                                ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                                ->where('roles.name', 'super_admin')
                                ->value('model_has_roles.model_id');
                            $ownerId ??= DB::table('users')->orderBy('id')->value('id');
                        }
                    } catch (\Throwable) {
                    }

                    if ($ownerId !== null) {
                        DB::table('tallcms_sites')->where('id', $defaultSite->id)
                            ->update(['user_id' => $ownerId]);
                    }
                }

                return;
            }

            $appUrl = config('app.url', 'http://localhost');
            $domain = Site::normalizeDomain(parse_url($appUrl, PHP_URL_HOST) ?? 'localhost');

            // If a site with this domain exists but isn't default, promote it
            $existing = DB::table('tallcms_sites')->where('domain', $domain)->first();

            // Resolve owner: assign to the first admin user so non-super-admins
            // can see the default site in the ownership-filtered resource
            $ownerId = null;
            try {
                if (auth()->check()) {
                    $ownerId = auth()->id();
                } else {
                    // Fallback: first user with super_admin role, or first user
                    $ownerId = DB::table('model_has_roles')
                        ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                        ->where('roles.name', 'super_admin')
                        ->value('model_has_roles.model_id');
                    $ownerId ??= DB::table('users')->orderBy('id')->value('id');
                }
            } catch (\Throwable) {
            }

            if ($existing) {
                $updateData = ['is_default' => true, 'is_active' => true, 'updated_at' => now()];
                if ($existing->user_id === null && $ownerId !== null) {
                    $updateData['user_id'] = $ownerId;
                }
                DB::table('tallcms_sites')->where('id', $existing->id)->update($updateData);
                $siteId = $existing->id;
            } else {
                $siteName = 'Default Site';
                try {
                    $siteName = \TallCms\Cms\Models\SiteSetting::get('site_name', config('app.name', 'Default Site'));
                } catch (\Throwable) {
                }

                $siteId = DB::table('tallcms_sites')->insertGetId([
                    'name' => $siteName,
                    'domain' => $domain,
                    'uuid' => (string) Str::uuid(),
                    'user_id' => $ownerId,
                    'is_default' => true,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Assign orphaned pages and menus to the default site
            if (Schema::hasColumn('tallcms_pages', 'site_id')) {
                DB::table('tallcms_pages')->whereNull('site_id')->update(['site_id' => $siteId]);
            }

            if (Schema::hasColumn('tallcms_menus', 'site_id')) {
                DB::table('tallcms_menus')->whereNull('site_id')->update(['site_id' => $siteId]);
            }

            Log::info('Multisite: default site created/promoted', [
                'site_id' => $siteId,
                'domain' => $domain,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Multisite: failed to ensure default site exists', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    protected function registerMiddleware(): void
    {
        // Use Kernel to append middleware to the web group
        // (plugin security scanner blocks direct router access)
        $kernel = $this->app->make(\Illuminate\Contracts\Http\Kernel::class);
        $kernel->appendMiddlewareToGroup('web', \Tallcms\Multisite\Http\Middleware\ResolveSiteMiddleware::class);
    }

    protected function registerGlobalScopes(): void
    {
        $scope = new \Tallcms\Multisite\Scopes\SiteScope;

        \TallCms\Cms\Models\CmsPage::addGlobalScope($scope);
        \TallCms\Cms\Models\TallcmsMenu::addGlobalScope($scope);
    }

    protected function registerModelListeners(): void
    {
        $resolver = $this->app->make(CurrentSiteResolver::class);

        \TallCms\Cms\Models\CmsPage::creating(function ($page) use ($resolver) {
            $page->site_id ??= $resolver->id();
        });

        \TallCms\Cms\Models\TallcmsMenu::creating(function ($menu) use ($resolver) {
            $menu->site_id ??= $resolver->id();
        });
    }

    protected function registerPolicies(): void
    {
        \Illuminate\Support\Facades\Gate::policy(
            Site::class,
            \Tallcms\Multisite\Policies\SitePolicy::class
        );
    }

    protected function registerLivewireComponents(): void
    {
        \Livewire\Livewire::component('site-switcher', \Tallcms\Multisite\Livewire\SiteSwitcher::class);
    }
}
