<?php

declare(strict_types=1);

namespace BezhanSalleh\PluginEssentials\Concerns\Plugin;

use Filament\Support\Concerns\EvaluatesClosures;

trait HasPluginDefaults
{
    use EvaluatesClosures;

    protected array $userSetProperties = [];

    /**
     * Get a property value with plugin defaults fallback:
     * 1. User-set values (tracked via fluent API)
     * 2. Plugin developer defaults
     * 3. Return null (let Resource handle its defaults)
     */
    protected function getPropertyWithDefaults(string $property, ?string $resourceClass = null): mixed
    {
        // 1. Check user-set values (highest priority)
        $userValue = null;

        if (method_exists($this, 'getContextualProperty')) {
            $userValue = $this->getContextualProperty($property, $resourceClass);
        }

        // If no contextual value, check if user explicitly set this property
        if ($userValue === null && $this->isPropertyUserSet($property)) {
            $userValue = $this->$property ?? null;
        }

        if ($userValue !== null) {
            return $this->evaluate($userValue);
        }

        // 2. Check plugin developer defaults (middle priority)
        $pluginDefault = $this->getPluginDefault($property, $resourceClass);
        if ($pluginDefault !== null) {
            return $this->evaluate($pluginDefault);
        }

        // 3. Return null - let Resource handle its own defaults
        return null;
    }

    protected function markPropertyAsUserSet(string $property): void
    {
        $this->userSetProperties[$property] = true;
    }

    protected function isPropertyUserSet(string $property): bool
    {
        return isset($this->userSetProperties[$property]);
    }

    protected function setUserProperty(string $property, mixed $value): static
    {
        $this->$property = $value;
        $this->markPropertyAsUserSet($property);

        return $this;
    }

    protected function getPluginDefault(string $property, ?string $resourceClass = null): mixed
    {
        // Try specific method first (e.g., getDefaultNavigationIcon)
        $specificMethod = 'getDefault' . ucfirst($property);
        if (method_exists($this, $specificMethod)) {
            return $this->$specificMethod($resourceClass);
        }

        // Try array-based defaults
        if (method_exists($this, 'getPluginDefaults')) {
            $defaults = $this->getPluginDefaults();

            // Check for forResource-specific defaults first
            if ($resourceClass !== null && $resourceClass !== '' && $resourceClass !== '0') {
                // New nested structure: 'resources' => [ResourceClass::class => [...]]
                if (isset($defaults['resources'][$resourceClass][$property])) {
                    return $defaults['resources'][$resourceClass][$property];
                }

                // Legacy flat structure: ResourceClass::class => [...]
                if (isset($defaults[$resourceClass][$property])) {
                    return $defaults[$resourceClass][$property];
                }
            }

            // Check for global defaults
            if (isset($defaults[$property])) {
                return $defaults[$property];
            }
        }

        return null;
    }
}
