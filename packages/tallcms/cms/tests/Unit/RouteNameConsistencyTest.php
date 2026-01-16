<?php

namespace TallCms\Cms\Tests\Unit;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Verify that all route() calls in package code use tallcms.* prefix.
 * This prevents the "Route [preview.page] not defined" errors in plugin mode.
 */
class RouteNameConsistencyTest extends TestCase
{
    /**
     * Route names that MUST use tallcms.* prefix
     */
    private array $requiredPrefixRoutes = [
        'preview.page',
        'preview.post',
        'preview.token',
        'contact.submit',
        'cms.home',
        'cms.page',
    ];

    /**
     * Route names that are correctly prefixed
     */
    private array $correctRouteNames = [
        'tallcms.preview.page',
        'tallcms.preview.post',
        'tallcms.preview.token',
        'tallcms.contact.submit',
        'tallcms.cms.home',
        'tallcms.cms.page',
    ];

    public function test_all_route_calls_use_tallcms_prefix(): void
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

            $content = file_get_contents($file->getPathname());
            $relativePath = str_replace(__DIR__ . '/../../', '', $file->getPathname());

            // Find all route() calls
            preg_match_all('/route\s*\(\s*[\'"]([^\'"]+)[\'"]/m', $content, $matches);

            foreach ($matches[1] as $routeName) {
                // Check if it's a route that should have tallcms.* prefix but doesn't
                foreach ($this->requiredPrefixRoutes as $unprefixedRoute) {
                    if ($routeName === $unprefixedRoute) {
                        $errors[] = "{$relativePath}: Uses '{$routeName}' instead of 'tallcms.{$routeName}'";
                    }
                }
            }
        }

        $this->assertEmpty(
            $errors,
            "Found route() calls without tallcms.* prefix:\n" . implode("\n", $errors)
        );
    }

    public function test_all_blade_route_calls_use_tallcms_prefix(): void
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
            $relativePath = str_replace(__DIR__ . '/../../', '', $file->getPathname());

            // Find all route() calls in Blade files
            preg_match_all('/route\s*\(\s*[\'"]([^\'"]+)[\'"]/m', $content, $matches);

            foreach ($matches[1] as $routeName) {
                // Check if it's a route that should have tallcms.* prefix but doesn't
                foreach ($this->requiredPrefixRoutes as $unprefixedRoute) {
                    if ($routeName === $unprefixedRoute) {
                        $errors[] = "{$relativePath}: Uses '{$routeName}' instead of 'tallcms.{$routeName}'";
                    }
                }
            }
        }

        $this->assertEmpty(
            $errors,
            "Found route() calls in Blade files without tallcms.* prefix:\n" . implode("\n", $errors)
        );
    }

    public function test_preview_token_model_uses_correct_route(): void
    {
        $modelPath = __DIR__ . '/../../src/Models/CmsPreviewToken.php';
        $content = file_get_contents($modelPath);

        $this->assertStringContainsString(
            "route('tallcms.preview.token'",
            $content,
            'CmsPreviewToken::getPreviewUrl() should use tallcms.preview.token route'
        );
    }

    public function test_edit_pages_use_correct_preview_routes(): void
    {
        $files = [
            __DIR__ . '/../../src/Filament/Resources/CmsPages/Pages/EditCmsPage.php',
            __DIR__ . '/../../src/Filament/Resources/CmsPosts/Pages/EditCmsPost.php',
        ];

        foreach ($files as $filePath) {
            if (! file_exists($filePath)) {
                continue;
            }

            $content = file_get_contents($filePath);
            $fileName = basename($filePath);

            $this->assertStringContainsString(
                "route('tallcms.preview.",
                $content,
                "{$fileName} should use tallcms.preview.* routes"
            );

            $this->assertStringNotContainsString(
                "route('preview.",
                $content,
                "{$fileName} should NOT use unprefixed preview.* routes"
            );
        }
    }

    public function test_contact_form_view_uses_correct_route(): void
    {
        $viewPath = __DIR__ . '/../../resources/views/cms/blocks/contact-form.blade.php';
        $content = file_get_contents($viewPath);

        $this->assertStringContainsString(
            "route('tallcms.contact.submit')",
            $content,
            'contact-form.blade.php should use tallcms.contact.submit route'
        );
    }
}
