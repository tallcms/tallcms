<?php

namespace App\Services;

use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use ReflectionClass;
use ReflectionException;

class CustomBlockDiscoveryService
{
    /**
     * Cached discovered blocks
     */
    protected static ?Collection $discoveredBlocks = null;

    /**
     * Discover all custom blocks in the application
     */
    public static function discover(): Collection
    {
        if (self::$discoveredBlocks !== null) {
            return self::$discoveredBlocks;
        }

        $blocks = collect();
        $blockPath = app_path('Filament/Forms/Components/RichEditor/RichContentCustomBlocks');

        if (! File::exists($blockPath)) {
            return self::$discoveredBlocks = $blocks;
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

        return self::$discoveredBlocks = $blocks->sort();
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
}
