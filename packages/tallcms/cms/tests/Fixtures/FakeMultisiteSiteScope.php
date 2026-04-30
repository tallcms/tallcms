<?php

declare(strict_types=1);

/**
 * Test-only stub of the multisite plugin's SiteScope class. Lives in a
 * bracketed namespace so PHP registers the class at exactly
 * Tallcms\Multisite\Scopes\SiteScope — the FQN that
 * tallcms_multisite_active() probes via class_exists() and
 * CmsPage::hasGlobalScope().
 *
 * Loaded by ScopesQueryToOwnedSitesTest to flip the helper into
 * "multisite is active" for those tests, without depending on the actual
 * multisite plugin being installed in the cms package's composer tree.
 */
namespace Tallcms\Multisite\Scopes {
    use Illuminate\Database\Eloquent\Builder;
    use Illuminate\Database\Eloquent\Model;
    use Illuminate\Database\Eloquent\Scope;

    if (! class_exists(SiteScope::class, false)) {
        class SiteScope implements Scope
        {
            public function apply(Builder $builder, Model $model): void
            {
                // Test stub — no-op. The real plugin's SiteScope filters
                // by current frontend site; in tests we only need the
                // class to exist + be registered as a global scope.
            }
        }
    }
}
