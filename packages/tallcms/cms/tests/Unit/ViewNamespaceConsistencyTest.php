<?php

namespace TallCms\Cms\Tests\Unit;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Verify that all view() calls in package code use tallcms:: namespace.
 * This prevents "View [cms.blocks.xyz] not found" errors in plugin mode.
 */
class ViewNamespaceConsistencyTest extends TestCase
{
    /**
     * View paths that MUST use tallcms:: namespace
     */
    private array $packageViewPaths = [
        'cms.blocks.',
        'cms.pages.',
        'layouts.',
        'livewire.',
        'preview.',
        'filament.',
        'components.',
        'maintenance',
    ];

    public function test_all_view_calls_use_tallcms_namespace(): void
    {
        $srcPath = __DIR__ . '/../../src';
        $errors = [];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($srcPath)
        );

        foreach ($iterator as $file) {
            if ($file->isDir() || $file->getExtension() !== 'php') {
                continue;
            }

            // Skip code generation stubs (they emit user-namespace view calls)
            if (str_contains($file->getPathname(), '/Console/Commands/')) {
                continue;
            }

            $content = file_get_contents($file->getPathname());
            $relativePath = str_replace(__DIR__ . '/../../', '', $file->getPathname());

            // Find all view() calls
            preg_match_all('/view\s*\(\s*[\'"]([^\'"]+)[\'"]/m', $content, $matches);

            foreach ($matches[1] as $viewName) {
                // Skip if already namespaced
                if (str_contains($viewName, '::')) {
                    continue;
                }

                // Check if it's a package view that should have tallcms:: namespace
                foreach ($this->packageViewPaths as $packagePath) {
                    if (str_starts_with($viewName, $packagePath)) {
                        $errors[] = "{$relativePath}: Uses '{$viewName}' instead of 'tallcms::{$viewName}'";
                        break;
                    }
                }
            }
        }

        $this->assertEmpty(
            $errors,
            "Found view() calls without tallcms:: namespace:\n" . implode("\n", $errors)
        );
    }

    public function test_block_classes_use_namespaced_views(): void
    {
        $blocksPath = __DIR__ . '/../../src/Filament/Blocks';
        $errors = [];

        if (! is_dir($blocksPath)) {
            $this->markTestSkipped('Blocks directory not found');
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($blocksPath)
        );

        foreach ($iterator as $file) {
            if ($file->isDir() || $file->getExtension() !== 'php') {
                continue;
            }

            // Skip Concerns directory
            if (str_contains($file->getPathname(), '/Concerns/')) {
                continue;
            }

            $content = file_get_contents($file->getPathname());
            $fileName = basename($file->getPathname());

            // Find view() calls
            preg_match_all('/view\s*\(\s*[\'"]([^\'"]+)[\'"]/m', $content, $matches);

            foreach ($matches[1] as $viewName) {
                if (str_starts_with($viewName, 'cms.blocks.') && ! str_contains($viewName, '::')) {
                    $errors[] = "{$fileName}: Uses '{$viewName}' instead of 'tallcms::{$viewName}'";
                }
            }
        }

        $this->assertEmpty(
            $errors,
            "Found block classes with unnamespaced view() calls:\n" . implode("\n", $errors)
        );
    }

    public function test_livewire_components_use_namespaced_views(): void
    {
        $livewirePath = __DIR__ . '/../../src/Livewire';
        $errors = [];

        if (! is_dir($livewirePath)) {
            $this->markTestSkipped('Livewire directory not found');
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($livewirePath)
        );

        foreach ($iterator as $file) {
            if ($file->isDir() || $file->getExtension() !== 'php') {
                continue;
            }

            $content = file_get_contents($file->getPathname());
            $fileName = basename($file->getPathname());

            // Find view() calls
            preg_match_all('/view\s*\(\s*[\'"]([^\'"]+)[\'"]/m', $content, $matches);

            foreach ($matches[1] as $viewName) {
                if (str_starts_with($viewName, 'livewire.') && ! str_contains($viewName, '::')) {
                    $errors[] = "{$fileName}: Uses '{$viewName}' instead of 'tallcms::{$viewName}'";
                }
            }
        }

        $this->assertEmpty(
            $errors,
            "Found Livewire components with unnamespaced view() calls:\n" . implode("\n", $errors)
        );
    }

    public function test_controllers_use_namespaced_views(): void
    {
        $controllersPath = __DIR__ . '/../../src/Http/Controllers';
        $errors = [];

        if (! is_dir($controllersPath)) {
            $this->markTestSkipped('Controllers directory not found');
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($controllersPath)
        );

        foreach ($iterator as $file) {
            if ($file->isDir() || $file->getExtension() !== 'php') {
                continue;
            }

            $content = file_get_contents($file->getPathname());
            $fileName = basename($file->getPathname());

            // Find view() calls
            preg_match_all('/view\s*\(\s*[\'"]([^\'"]+)[\'"]/m', $content, $matches);

            foreach ($matches[1] as $viewName) {
                // Skip if already namespaced
                if (str_contains($viewName, '::')) {
                    continue;
                }

                // Check common package view paths
                if (str_starts_with($viewName, 'preview.') ||
                    str_starts_with($viewName, 'cms.') ||
                    str_starts_with($viewName, 'layouts.')) {
                    $errors[] = "{$fileName}: Uses '{$viewName}' instead of 'tallcms::{$viewName}'";
                }
            }
        }

        $this->assertEmpty(
            $errors,
            "Found controllers with unnamespaced view() calls:\n" . implode("\n", $errors)
        );
    }

    public function test_blade_components_reference_namespaced_views(): void
    {
        $viewsPath = __DIR__ . '/../../resources/views';
        $errors = [];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($viewsPath)
        );

        foreach ($iterator as $file) {
            if ($file->isDir() || $file->getExtension() !== 'php') {
                continue;
            }

            $content = file_get_contents($file->getPathname());
            $relativePath = str_replace(__DIR__ . '/../../resources/views/', '', $file->getPathname());

            // Find x-component references that should use tallcms:: namespace
            // Pattern: <x-something> where something is NOT tallcms::
            preg_match_all('/<x-([a-z][a-z0-9\-\.]+)(?:\s|>|\/)/i', $content, $matches);

            foreach ($matches[1] as $componentName) {
                // Skip if already namespaced with tallcms::
                if (str_starts_with($componentName, 'tallcms::')) {
                    continue;
                }

                // Skip common Laravel/Livewire components
                if (in_array($componentName, ['slot', 'dynamic-component', 'livewire'])) {
                    continue;
                }

                // Skip heroicon components
                if (str_starts_with($componentName, 'heroicon-')) {
                    continue;
                }

                // Check if this is a package component that should be namespaced
                $packageComponents = ['menu', 'menu-item', 'form.dynamic-field'];
                if (in_array($componentName, $packageComponents)) {
                    $errors[] = "{$relativePath}: Uses <x-{$componentName}> instead of <x-tallcms::{$componentName}>";
                }
            }
        }

        $this->assertEmpty(
            $errors,
            "Found Blade templates with unnamespaced component references:\n" . implode("\n", $errors)
        );
    }
}
