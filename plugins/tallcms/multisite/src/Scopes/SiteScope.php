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

        // Only filter when site is resolved
        if (! $resolver->isResolved()) {
            return;
        }

        $siteId = $resolver->id();

        if ($siteId) {
            // Strict: only show content belonging to this site
            $builder->where($model->getTable().'.site_id', $siteId);
        }

        // If resolver ran but returned no site (e.g., "All Sites" admin mode),
        // don't apply any filter — show everything
    }
}
