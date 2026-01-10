<?php

declare(strict_types=1);

namespace BezhanSalleh\PluginEssentials\Concerns\Resource;

trait BelongsToTenant
{
    use DelegatesToPlugin;

    public static function isScopedToTenant(): bool
    {
        $pluginResult = static::delegateToPlugin('BelongsToTenant', 'shouldScopeToTenant');

        if (! static::isNoPluginResult($pluginResult)) {
            return $pluginResult;
        }

        return static::getParentResult('isScopedToTenant');
    }

    public static function getTenantRelationshipName(): string
    {
        $pluginResult = static::delegateToPlugin(
            traitName: 'BelongsToTenant',
            methodName: 'getTenantRelationshipName',
        );

        if (! static::isNoPluginResult($pluginResult) && $pluginResult !== null) {
            return $pluginResult;
        }

        return static::getParentResult('getTenantRelationshipName');
    }

    public static function getTenantOwnershipRelationshipName(): string
    {
        $pluginResult = static::delegateToPlugin('BelongsToTenant', 'getTenantOwnershipRelationshipName');

        if (! static::isNoPluginResult($pluginResult) && $pluginResult !== null) {
            return $pluginResult;
        }

        return static::getParentResult('getTenantOwnershipRelationshipName');
    }
}
