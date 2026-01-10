<?php

declare(strict_types=1);

namespace BezhanSalleh\PluginEssentials\Concerns\Resource;

trait HasGlobalSearch
{
    use DelegatesToPlugin;

    public static function canGloballySearch(): bool
    {
        $pluginResult = static::delegateToPlugin(
            'HasGlobalSearch',
            'canGloballySearch',
            null
        );

        return static::isNoPluginResult($pluginResult)
            ? static::getParentResult('canGloballySearch')
            : $pluginResult;
    }

    public static function getGlobalSearchResultsLimit(): int
    {
        $pluginResult = static::delegateToPlugin(
            'HasGlobalSearch',
            'getGlobalSearchResultsLimit',
            null
        );

        return static::isNoPluginResult($pluginResult)
            ? static::getParentResult('getGlobalSearchResultsLimit')
            : (int) $pluginResult;
    }

    public static function isGlobalSearchForcedCaseInsensitive(): ?bool
    {
        $pluginResult = static::delegateToPlugin(
            'HasGlobalSearch',
            'isGlobalSearchForcedCaseInsensitive',
            null
        );

        return static::isNoPluginResult($pluginResult)
            ? static::getParentResult('isGlobalSearchForcedCaseInsensitive')
            : $pluginResult;
    }

    public static function shouldSplitGlobalSearchTerms(): bool
    {
        $pluginResult = static::delegateToPlugin(
            'HasGlobalSearch',
            'shouldSplitGlobalSearchTerms',
            null
        );

        return static::isNoPluginResult($pluginResult)
            ? static::getParentResult('shouldSplitGlobalSearchTerms')
            : $pluginResult;
    }
}
