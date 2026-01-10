<?php

declare(strict_types=1);

namespace BezhanSalleh\PluginEssentials\Concerns\Plugin;

use Closure;

trait BelongsToTenant
{
    use HasPluginDefaults;

    protected bool | Closure $isScopedToTenant = true;

    protected string | Closure | null $tenantOwnershipRelationshipName = null;

    protected string | Closure | null $tenantRelationshipName = null;

    public function scopeToTenant(bool | Closure $condition = true): static
    {
        if (method_exists($this, 'setContextualProperty')) {
            return $this->setContextualProperty('isScopedToTenant', $condition);
        }

        $this->isScopedToTenant = $condition;
        $this->markPropertyAsUserSet('isScopedToTenant');

        return $this;
    }

    public function tenantOwnershipRelationshipName(string | Closure | null $ownershipRelationshipName): static
    {
        if (method_exists($this, 'setContextualProperty')) {
            return $this->setContextualProperty('tenantOwnershipRelationshipName', $ownershipRelationshipName);
        }

        $this->tenantOwnershipRelationshipName = $ownershipRelationshipName;
        $this->markPropertyAsUserSet('tenantOwnershipRelationshipName');

        return $this;
    }

    public function tenantRelationshipName(string | Closure | null $relationshipName): static
    {
        if (method_exists($this, 'setContextualProperty')) {
            return $this->setContextualProperty('tenantRelationshipName', $relationshipName);
        }

        $this->tenantRelationshipName = $relationshipName;
        $this->markPropertyAsUserSet('tenantRelationshipName');

        return $this;
    }

    public function isScopedToTenant(?string $resourceClass = null): bool
    {
        $result = $this->getPropertyWithDefaults('isScopedToTenant', $resourceClass);

        return $result ?? true; // Default to true only if no value found
    }

    public function shouldScopeToTenant(?string $resourceClass = null): bool
    {
        $result = $this->getPropertyWithDefaults('isScopedToTenant', $resourceClass);

        return $result ?? true; // Default to true only if no value found
    }

    public function getTenantRelationshipName(?string $resourceClass = null): ?string
    {
        return $this->getPropertyWithDefaults('tenantRelationshipName', $resourceClass);
    }

    public function getTenantOwnershipRelationshipName(?string $resourceClass = null): ?string
    {
        return $this->getPropertyWithDefaults('tenantOwnershipRelationshipName', $resourceClass);
    }
}
