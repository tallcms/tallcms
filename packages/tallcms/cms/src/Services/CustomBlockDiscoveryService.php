<?php

declare(strict_types=1);

namespace TallCms\Cms\Services;

use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use ReflectionClass;
use ReflectionException;
use TallCms\Cms\Services\BlockCategoryRegistry;

class CustomBlockDiscoveryService
{
    /**
     * Cached discovered blocks
     */
    protected static ?Collection $discoveredBlocks = null;

    /**
     * Discover all custom blocks in the application, package, and plugins.
     *
     * Precedence (later sources override earlier by block ID):
     * 1. Package blocks (base)
     * 2. App blocks (override package)
     * 3. Plugin blocks (override all)
     */
    public static function discover(): Collection
    {
        if (self::$discoveredBlocks !== null) {
            return self::$discoveredBlocks;
        }

        $blocks = collect();

        // Discover blocks from the package (TallCms\Cms\Filament\Blocks)
        $packageBlockPath = dirname(__DIR__).'/Filament/Blocks';
        if (File::exists($packageBlockPath)) {
            $blocks = $blocks->merge(self::discoverFromPath($packageBlockPath));
        }

        // Discover blocks from the main application (can override package blocks)
        $appBlockPath = app_path('Filament/Blocks');
        if (File::exists($appBlockPath)) {
            $blocks = $blocks->merge(self::discoverFromPath($appBlockPath));
        }

        // Discover blocks from installed plugins (can override all)
        $blocks = $blocks->merge(self::discoverFromPlugins());

        // Dedupe by block ID - later sources override earlier ones
        // keyBy() overwrites earlier entries when a later entry has the same key
        $blocks = $blocks->keyBy(function ($className) {
            try {
                return $className::getId();
            } catch (\Throwable $e) {
                // Fallback to class name if getId() fails
                return $className;
            }
        })->values();

        return self::$discoveredBlocks = $blocks->sort()->values();
    }

    /**
     * Discover blocks from a specific path
     */
    protected static function discoverFromPath(string $blockPath): Collection
    {
        $blocks = collect();

        if (! File::exists($blockPath)) {
            return $blocks;
        }

        $blockFiles = File::glob($blockPath.'/*.php');

        foreach ($blockFiles as $file) {
            try {
                $className = self::getClassNameFromFile($file);

                if ($className && class_exists($className)) {
                    $reflection = new ReflectionClass($className);

                    // Only include classes that extend RichContentCustomBlock and are not abstract
                    if ($reflection->isSubclassOf(RichContentCustomBlock::class) && ! $reflection->isAbstract()) {
                        $blocks->push($className);
                    }
                }
            } catch (ReflectionException $e) {
                // Skip files that can't be reflected (invalid PHP, etc.)
                continue;
            }
        }

        return $blocks;
    }

    /**
     * Discover blocks from installed plugins
     */
    protected static function discoverFromPlugins(): Collection
    {
        $blocks = collect();

        if (! app()->bound('plugin.manager')) {
            return $blocks;
        }

        try {
            $pluginManager = app('plugin.manager');
            $plugins = $pluginManager->getInstalledPlugins();

            foreach ($plugins as $plugin) {
                if (! $plugin->hasBlocks()) {
                    continue;
                }

                $blocksPath = $plugin->getBlocksPath();
                $blockFiles = File::glob($blocksPath.'/*.php');

                foreach ($blockFiles as $file) {
                    try {
                        $className = self::getClassNameFromFile($file);

                        if ($className && class_exists($className)) {
                            $reflection = new ReflectionClass($className);

                            if ($reflection->isSubclassOf(RichContentCustomBlock::class) && ! $reflection->isAbstract()) {
                                $blocks->push($className);
                            }
                        }
                    } catch (ReflectionException $e) {
                        continue;
                    }
                }
            }
        } catch (\Throwable $e) {
            // Don't fail if plugin system has issues
        }

        return $blocks;
    }

    /**
     * Get the full class name from a PHP file
     */
    protected static function getClassNameFromFile(string $filePath): ?string
    {
        $content = File::get($filePath);

        // Extract namespace
        $namespacePattern = '/namespace\s+([^;]+);/';
        preg_match($namespacePattern, $content, $namespaceMatches);
        $namespace = $namespaceMatches[1] ?? null;

        if (! $namespace) {
            return null;
        }

        // Extract class name
        $classPattern = '/class\s+(\w+)/';
        preg_match($classPattern, $content, $classMatches);
        $className = $classMatches[1] ?? null;

        if (! $className) {
            return null;
        }

        return $namespace.'\\'.$className;
    }

    /**
     * Get discovered blocks as array (for use in Filament configurations)
     */
    public static function getBlocksArray(): array
    {
        return self::discover()->toArray();
    }

    /**
     * Clear the discovery cache (useful for testing or development)
     */
    public static function clearCache(): void
    {
        self::$discoveredBlocks = null;
    }

    /**
     * Get blocks formatted for display (with labels)
     */
    public static function getBlocksWithLabels(): Collection
    {
        return self::discover()->map(function ($blockClass) {
            try {
                return [
                    'class' => $blockClass,
                    'id' => $blockClass::getId(),
                    'label' => $blockClass::getLabel(),
                ];
            } catch (\Exception $e) {
                return [
                    'class' => $blockClass,
                    'id' => 'unknown',
                    'label' => class_basename($blockClass),
                ];
            }
        });
    }

    /**
     * Get blocks with full metadata for enhanced block panel.
     *
     * Includes category, icon, description, keywords, and precomputed search string.
     */
    public static function getBlocksWithMetadata(): Collection
    {
        return self::discover()->map(function ($blockClass) {
            $icon = method_exists($blockClass, 'getIcon') ? $blockClass::getIcon() : null;
            $label = $blockClass::getLabel();
            $description = method_exists($blockClass, 'getDescription') ? $blockClass::getDescription() : '';
            $id = $blockClass::getId();
            $keywords = method_exists($blockClass, 'getKeywords') ? $blockClass::getKeywords() : [];

            $validatedIcon = self::validateIcon($icon);

            return [
                'class' => $blockClass,
                'id' => $id,
                'label' => $label,
                'category' => method_exists($blockClass, 'getCategory') ? $blockClass::getCategory() : 'other',
                'icon' => $validatedIcon,
                'iconHtml' => self::renderIconHtml($validatedIcon),
                'description' => $description,
                'keywords' => $keywords,
                'sortPriority' => method_exists($blockClass, 'getSortPriority') ? $blockClass::getSortPriority() : 50,
                'searchable' => self::normalizeSearchable(
                    implode(' ', [$label, $description, $id, implode(' ', $keywords)])
                ),
            ];
        });
    }

    /**
     * Get blocks grouped by category for the enhanced block panel.
     *
     * Returns blocks organized by category, sorted by category order and block priority.
     */
    public static function getBlocksGroupedByCategory(): Collection
    {
        $blocks = self::getBlocksWithMetadata();
        $categories = BlockCategoryRegistry::getCategories();

        return $blocks
            ->groupBy('category')
            ->map(fn ($group) => $group->sortBy([
                ['sortPriority', 'asc'],
                ['label', 'asc'],
            ])->values()->toArray())
            ->sortBy(fn ($_, $cat) => $categories[$cat]['order'] ?? 999);
    }

    /**
     * Normalize a string for search matching.
     *
     * Converts to lowercase and normalizes whitespace.
     */
    protected static function normalizeSearchable(string $text): string
    {
        $normalized = preg_replace('/\s+/', ' ', trim($text));

        return function_exists('mb_strtolower')
            ? mb_strtolower($normalized, 'UTF-8')
            : strtolower($normalized);
    }

    /**
     * Validate an icon name and return a fallback if invalid.
     *
     * Uses Blade Icons svg() helper to check if icon exists.
     */
    protected static function validateIcon(?string $icon): string
    {
        if (! $icon) {
            return BlockCategoryRegistry::FALLBACK_ICON;
        }

        // Check if svg() helper exists (Blade Icons package)
        if (! function_exists('svg')) {
            return $icon; // Can't validate, assume valid
        }

        // Check if icon exists via Blade Icons
        try {
            svg($icon);

            return $icon;
        } catch (\Throwable) {
            return BlockCategoryRegistry::FALLBACK_ICON;
        }
    }

    /**
     * Pre-render icon HTML for use in JavaScript.
     *
     * Returns safe HTML string that can be used with x-html in Alpine.
     */
    protected static function renderIconHtml(string $icon): string
    {
        try {
            return \Filament\Support\generate_icon_html($icon, 'h-5 w-5')->toHtml();
        } catch (\Throwable) {
            // Fallback to FALLBACK_ICON if generate_icon_html throws
            return \Filament\Support\generate_icon_html(BlockCategoryRegistry::FALLBACK_ICON, 'h-5 w-5')->toHtml();
        }
    }
}
