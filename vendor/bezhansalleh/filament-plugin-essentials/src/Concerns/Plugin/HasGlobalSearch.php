<?php

declare(strict_types=1);

namespace BezhanSalleh\PluginEssentials\Concerns\Plugin;

use Closure;

trait HasGlobalSearch
{
    use HasPluginDefaults;

    protected int $globalSearchResultsLimit = 50;

    protected bool | Closure $isGloballySearchable = true;

    protected bool | Closure | null $isGlobalSearchForcedCaseInsensitive = null;

    protected bool | Closure $shouldSplitGlobalSearchTerms = false;

    public function globalSearchResultsLimit(int $limit): static
    {
        if (method_exists($this, 'setContextualProperty')) {
            return $this->setContextualProperty('globalSearchResultsLimit', $limit);
        }

        $this->globalSearchResultsLimit = $limit;
        $this->markPropertyAsUserSet('globalSearchResultsLimit');

        return $this;
    }

    public function globallySearchable(bool | Closure $condition = true): static
    {
        if (method_exists($this, 'setContextualProperty')) {
            return $this->setContextualProperty('isGloballySearchable', $condition);
        }

        $this->isGloballySearchable = $condition;
        $this->markPropertyAsUserSet('isGloballySearchable');

        return $this;
    }

    public function forceGlobalSearchCaseInsensitive(bool | Closure | null $condition = true): static
    {
        if (method_exists($this, 'setContextualProperty')) {
            return $this->setContextualProperty('isGlobalSearchForcedCaseInsensitive', $condition);
        }

        $this->isGlobalSearchForcedCaseInsensitive = $condition;
        $this->markPropertyAsUserSet('isGlobalSearchForcedCaseInsensitive');

        return $this;
    }

    public function splitGlobalSearchTerms(bool | Closure $condition = true): static
    {
        if (method_exists($this, 'setContextualProperty')) {
            return $this->setContextualProperty('shouldSplitGlobalSearchTerms', $condition);
        }

        $this->shouldSplitGlobalSearchTerms = $condition;
        $this->markPropertyAsUserSet('shouldSplitGlobalSearchTerms');

        return $this;
    }

    public function canGloballySearch(?string $resourceClass = null): bool
    {
        $result = $this->getPropertyWithDefaults('isGloballySearchable', $resourceClass);

        return $result ?? true; // Default to true only if no value found
    }

    public function getGlobalSearchResultsLimit(?string $resourceClass = null): int
    {
        $result = $this->getPropertyWithDefaults('globalSearchResultsLimit', $resourceClass);

        return $result ?? 50; // Default to 50 only if no value found
    }

    public function isGlobalSearchForcedCaseInsensitive(?string $resourceClass = null): ?bool
    {
        return $this->getPropertyWithDefaults('isGlobalSearchForcedCaseInsensitive', $resourceClass);
    }

    public function shouldSplitGlobalSearchTerms(?string $resourceClass = null): bool
    {
        $result = $this->getPropertyWithDefaults('shouldSplitGlobalSearchTerms', $resourceClass);

        return $result ?? false; // Default to false only if no value found
    }
}
