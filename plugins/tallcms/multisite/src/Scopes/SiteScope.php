<?php

declare(strict_types=1);

namespace Tallcms\Multisite\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Tallcms\Multisite\Services\CurrentSiteResolver;

class SiteScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $resolver = app(CurrentSiteResolver::class);

        // Lazily resolve if middleware hasn't run yet.
        // Filament admin uses its own middleware stack (not the 'web' group),
        // so ResolveSiteMiddleware may not have executed.
        if (! $resolver->isResolved() && app()->runningInConsole() === false) {
            $resolver->resolve(request());
        }

        if (! $resolver->isResolved()) {
            return;
        }

        $siteId = $resolver->id();

        if ($siteId) {
            // Strict: only show content belonging to this site
            $builder->where($model->getTable().'.site_id', $siteId);
        } elseif ($resolver->isAllSitesMode()) {
            // Explicit "All Sites" admin mode — no filter, show everything
        } else {
            // Resolved but no site (unknown domain, no admin selection).
            // Show nothing to prevent cross-site content leakage.
            $builder->whereRaw('1 = 0');
        }
    }
}
