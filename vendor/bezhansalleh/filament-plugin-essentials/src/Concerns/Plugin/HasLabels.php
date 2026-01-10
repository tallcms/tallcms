<?php

declare(strict_types=1);

namespace BezhanSalleh\PluginEssentials\Concerns\Plugin;

use Closure;

trait HasLabels
{
    use HasPluginDefaults;

    protected string | Closure | null $modelLabel = null;

    protected string | Closure | null $pluralModelLabel = null;

    protected string | Closure | null $recordTitleAttribute = null;

    protected bool | Closure $hasTitleCaseModelLabel = true;

    public function modelLabel(string | Closure | null $label): static
    {
        if (method_exists($this, 'setContextualProperty')) {
            return $this->setContextualProperty('modelLabel', $label);
        }

        $this->modelLabel = $label;
        $this->markPropertyAsUserSet('modelLabel');

        return $this;
    }

    public function pluralModelLabel(string | Closure | null $label): static
    {
        if (method_exists($this, 'setContextualProperty')) {
            return $this->setContextualProperty('pluralModelLabel', $label);
        }

        $this->pluralModelLabel = $label;
        $this->markPropertyAsUserSet('pluralModelLabel');

        return $this;
    }

    public function titleCaseModelLabel(bool | Closure $condition = true): static
    {
        if (method_exists($this, 'setContextualProperty')) {
            return $this->setContextualProperty('hasTitleCaseModelLabel', $condition);
        }

        $this->hasTitleCaseModelLabel = $condition;
        $this->markPropertyAsUserSet('hasTitleCaseModelLabel');

        return $this;
    }

    public function recordTitleAttribute(string | Closure | null $attribute): static
    {
        if (method_exists($this, 'setContextualProperty')) {
            return $this->setContextualProperty('recordTitleAttribute', $attribute);
        }

        $this->recordTitleAttribute = $attribute;
        $this->markPropertyAsUserSet('recordTitleAttribute');

        return $this;
    }

    public function getModelLabel(?string $resourceClass = null): ?string
    {
        return $this->getPropertyWithDefaults('modelLabel', $resourceClass);
    }

    public function getPluralModelLabel(?string $resourceClass = null): ?string
    {
        return $this->getPropertyWithDefaults('pluralModelLabel', $resourceClass);
    }

    public function hasTitleCaseModelLabel(?string $resourceClass = null): bool
    {
        $result = $this->getPropertyWithDefaults('hasTitleCaseModelLabel', $resourceClass);

        return $result ?? true; // Default to true only if no value found
    }

    public function getRecordTitleAttribute(?string $resourceClass = null): ?string
    {
        return $this->getPropertyWithDefaults('recordTitleAttribute', $resourceClass);
    }
}
