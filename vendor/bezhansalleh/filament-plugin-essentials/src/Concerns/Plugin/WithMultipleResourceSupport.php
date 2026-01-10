<?php

declare(strict_types=1);

namespace BezhanSalleh\PluginEssentials\Concerns\Plugin;

trait WithMultipleResourceSupport
{
    protected array $resourceContexts = [];

    protected ?string $activeResourceContext = null;

    public function forResource(string $resourceClass): static
    {
        $this->activeResourceContext = $resourceClass;

        return $this;
    }

    protected function setContextualProperty(string $property, mixed $value): static
    {
        if ($this->activeResourceContext) {
            if (! isset($this->resourceContexts[$this->activeResourceContext])) {
                $this->resourceContexts[$this->activeResourceContext] = [];
            }

            $this->resourceContexts[$this->activeResourceContext][$property] = $value;

        } else {
            $this->$property = $value;

            if (method_exists($this, 'markPropertyAsUserSet')) {
                $this->markPropertyAsUserSet($property);
            }
        }

        return $this;
    }

    protected function getContextualProperty(string $property, ?string $resourceClass = null): mixed
    {
        if ($resourceClass && isset($this->resourceContexts[$resourceClass][$property])) {
            return $this->resourceContexts[$resourceClass][$property];
        }

        if (method_exists($this, 'isPropertyUserSet') && $this->isPropertyUserSet($property)) {
            return $this->$property ?? null;
        }

        return null;
    }

    public function supportsMultipleResources(): bool
    {
        return true;
    }
}
