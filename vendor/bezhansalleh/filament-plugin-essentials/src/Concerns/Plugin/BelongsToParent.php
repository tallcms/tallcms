<?php

declare(strict_types=1);

namespace BezhanSalleh\PluginEssentials\Concerns\Plugin;

trait BelongsToParent
{
    use HasPluginDefaults;

    /**
     * @var class-string | null
     */
    protected ?string $parentResource = null;

    public function parentResource(?string $resource): static
    {
        if (method_exists($this, 'setContextualProperty')) {
            return $this->setContextualProperty('parentResource', $resource);
        }

        $this->parentResource = $resource;
        $this->markPropertyAsUserSet('parentResource');

        return $this;
    }

    /**
     * @return class-string | null
     */
    public function getParentResource(?string $resourceClass = null): ?string
    {
        return $this->getPropertyWithDefaults('parentResource', $resourceClass);
    }
}
