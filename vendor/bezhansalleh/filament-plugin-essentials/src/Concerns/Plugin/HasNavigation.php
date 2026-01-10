<?php

declare(strict_types=1);

namespace BezhanSalleh\PluginEssentials\Concerns\Plugin;

use BackedEnum;
use Closure;
use Filament\Pages\Enums\SubNavigationPosition;
use Illuminate\Contracts\Support\Htmlable;
use UnitEnum;

trait HasNavigation
{
    use HasPluginDefaults;

    protected Closure | null | SubNavigationPosition $subNavigationPosition = null;

    protected bool | Closure $shouldRegisterNavigation = true;

    protected Closure | null | string $navigationBadgeTooltip = null;

    protected Closure | null | string | UnitEnum $navigationGroup = null;

    protected array | Closure | null | string $navigationBadgeColor = null;

    protected Closure | string | null $navigationBadge = null;

    protected Closure | string | null $navigationParentItem = null;

    protected BackedEnum | Closure | null | string $navigationIcon = null;

    protected BackedEnum | Closure | null | string $activeNavigationIcon = null;

    protected Closure | null | string $navigationLabel = null;

    protected Closure | int | null $navigationSort = null;

    public function subNavigationPosition(Closure | SubNavigationPosition $subNavigationPosition): static
    {
        if (method_exists($this, 'setContextualProperty')) {
            return $this->setContextualProperty('subNavigationPosition', $subNavigationPosition);
        }

        $this->subNavigationPosition = $subNavigationPosition;
        $this->markPropertyAsUserSet('subNavigationPosition');

        return $this;
    }

    public function registerNavigation(bool | Closure $shouldRegisterNavigation): static
    {
        if (method_exists($this, 'setContextualProperty')) {
            return $this->setContextualProperty('shouldRegisterNavigation', $shouldRegisterNavigation);
        }

        $this->shouldRegisterNavigation = $shouldRegisterNavigation;
        $this->markPropertyAsUserSet('shouldRegisterNavigation');

        return $this;
    }

    public function navigationBadgeTooltip(string | Closure | null $tooltip): static
    {
        if (method_exists($this, 'setContextualProperty')) {
            return $this->setContextualProperty('navigationBadgeTooltip', $tooltip);
        }

        $this->navigationBadgeTooltip = $tooltip;
        $this->markPropertyAsUserSet('navigationBadgeTooltip');

        return $this;
    }

    public function navigationBadge(Closure | null | string $value = null): static
    {
        if (method_exists($this, 'setContextualProperty')) {
            return $this->setContextualProperty('navigationBadge', $value);
        }

        $this->navigationBadge = $value;
        $this->markPropertyAsUserSet('navigationBadge');

        return $this;
    }

    public function navigationBadgeColor(array | Closure | string $color): static
    {
        if (method_exists($this, 'setContextualProperty')) {
            return $this->setContextualProperty('navigationBadgeColor', $color);
        }

        $this->navigationBadgeColor = $color;
        $this->markPropertyAsUserSet('navigationBadgeColor');

        return $this;
    }

    public function navigationGroup(Closure | null | string | UnitEnum $group): static
    {
        if (method_exists($this, 'setContextualProperty')) {
            return $this->setContextualProperty('navigationGroup', $group);
        }

        $this->navigationGroup = $group;
        $this->markPropertyAsUserSet('navigationGroup');

        return $this;
    }

    public function navigationParentItem(string | Closure | null $item): static
    {
        if (method_exists($this, 'setContextualProperty')) {
            return $this->setContextualProperty('navigationParentItem', $item);
        }

        $this->navigationParentItem = $item;
        $this->markPropertyAsUserSet('navigationParentItem');

        return $this;
    }

    public function navigationIcon(BackedEnum | Closure | null | string $icon): static
    {
        if (method_exists($this, 'setContextualProperty')) {
            return $this->setContextualProperty('navigationIcon', $icon);
        }

        $this->navigationIcon = $icon;
        $this->markPropertyAsUserSet('navigationIcon');

        return $this;
    }

    public function activeNavigationIcon(BackedEnum | Closure | null | string $icon): static
    {
        if (method_exists($this, 'setContextualProperty')) {
            return $this->setContextualProperty('activeNavigationIcon', $icon);
        }

        $this->activeNavigationIcon = $icon;
        $this->markPropertyAsUserSet('activeNavigationIcon');

        return $this;
    }

    public function navigationLabel(Closure | null | string $label): static
    {
        if (method_exists($this, 'setContextualProperty')) {
            return $this->setContextualProperty('navigationLabel', $label);
        }

        $this->navigationLabel = $label;
        $this->markPropertyAsUserSet('navigationLabel');

        return $this;
    }

    public function navigationSort(int | Closure | null $sort): static
    {
        if (method_exists($this, 'setContextualProperty')) {
            return $this->setContextualProperty('navigationSort', $sort);
        }

        $this->navigationSort = $sort;
        $this->markPropertyAsUserSet('navigationSort');

        return $this;
    }

    public function getSubNavigationPosition(?string $resourceClass = null): SubNavigationPosition
    {
        $result = $this->getPropertyWithDefaults('subNavigationPosition', $resourceClass);

        return $result ?? SubNavigationPosition::Start;
    }

    public function shouldRegisterNavigation(?string $resourceClass = null): bool
    {
        $result = $this->getPropertyWithDefaults('shouldRegisterNavigation', $resourceClass);

        return $result ?? true; // Default to true only if no value found
    }

    public function getNavigationBadgeTooltip(?string $resourceClass = null): ?string
    {
        return $this->getPropertyWithDefaults('navigationBadgeTooltip', $resourceClass);
    }

    public function getNavigationBadge(?string $resourceClass = null): ?string
    {
        return $this->getPropertyWithDefaults('navigationBadge', $resourceClass);
    }

    public function getNavigationBadgeColor(?string $resourceClass = null): array | string | null
    {
        return $this->getPropertyWithDefaults('navigationBadgeColor', $resourceClass);
    }

    public function getNavigationGroup(?string $resourceClass = null): string | UnitEnum | null
    {
        return $this->getPropertyWithDefaults('navigationGroup', $resourceClass);
    }

    public function getNavigationParentItem(?string $resourceClass = null): ?string
    {
        return $this->getPropertyWithDefaults('navigationParentItem', $resourceClass);
    }

    public function getNavigationIcon(?string $resourceClass = null): BackedEnum | Htmlable | null | string
    {
        return $this->getPropertyWithDefaults('navigationIcon', $resourceClass);
    }

    public function getActiveNavigationIcon(?string $resourceClass = null): BackedEnum | Htmlable | null | string
    {
        return $this->getPropertyWithDefaults('activeNavigationIcon', $resourceClass);
    }

    public function getNavigationLabel(?string $resourceClass = null): string
    {
        return (string) $this->getPropertyWithDefaults('navigationLabel', $resourceClass);
    }

    public function getNavigationSort(?string $resourceClass = null): ?int
    {
        return $this->getPropertyWithDefaults('navigationSort', $resourceClass);
    }
}
