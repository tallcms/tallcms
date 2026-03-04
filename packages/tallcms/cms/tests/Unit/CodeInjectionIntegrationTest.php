<?php

namespace TallCms\Cms\Tests\Unit;

use PHPUnit\Framework\TestCase;

class CodeInjectionIntegrationTest extends TestCase
{
    private string $layoutPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->layoutPath = __DIR__ . '/../../resources/views/layouts/app.blade.php';
    }

    // --- Layout placement tests ---

    public function test_head_zone_is_inside_head_tag(): void
    {
        $layout = file_get_contents($this->layoutPath);

        $headOpenPos = strpos($layout, '<head');
        $headClosePos = strpos($layout, '</head>');
        $zonePos = strpos($layout, 'zone="head"');

        $this->assertNotFalse($headOpenPos, 'Layout must contain <head> tag');
        $this->assertNotFalse($headClosePos, 'Layout must contain </head> tag');
        $this->assertNotFalse($zonePos, 'Layout must contain code-injection zone="head"');
        $this->assertGreaterThan($headOpenPos, $zonePos, 'Head zone must appear after <head>');
        $this->assertLessThan($headClosePos, $zonePos, 'Head zone must appear before </head>');
    }

    public function test_body_start_zone_is_after_body_open_tag(): void
    {
        $layout = file_get_contents($this->layoutPath);

        $bodyOpenPos = strpos($layout, '<body');
        $bodyStartZonePos = strpos($layout, 'zone="body_start"');
        $bodyEndZonePos = strpos($layout, 'zone="body_end"');

        $this->assertNotFalse($bodyOpenPos, 'Layout must contain <body> tag');
        $this->assertNotFalse($bodyStartZonePos, 'Layout must contain code-injection zone="body_start"');
        $this->assertGreaterThan($bodyOpenPos, $bodyStartZonePos, 'body_start zone must appear after <body>');
        $this->assertLessThan($bodyEndZonePos, $bodyStartZonePos, 'body_start zone must appear before body_end zone');
    }

    public function test_body_end_zone_is_before_body_close_tag(): void
    {
        $layout = file_get_contents($this->layoutPath);

        $bodyClosePos = strpos($layout, '</body>');
        $bodyEndZonePos = strpos($layout, 'zone="body_end"');

        $this->assertNotFalse($bodyClosePos, 'Layout must contain </body> tag');
        $this->assertNotFalse($bodyEndZonePos, 'Layout must contain code-injection zone="body_end"');
        $this->assertLessThan($bodyClosePos, $bodyEndZonePos, 'body_end zone must appear before </body>');
    }

    public function test_zones_are_in_correct_relative_order(): void
    {
        $layout = file_get_contents($this->layoutPath);

        $headZonePos = strpos($layout, 'zone="head"');
        $bodyStartZonePos = strpos($layout, 'zone="body_start"');
        $bodyEndZonePos = strpos($layout, 'zone="body_end"');

        $this->assertLessThan($bodyStartZonePos, $headZonePos, 'Head zone must come before body_start zone');
        $this->assertLessThan($bodyEndZonePos, $bodyStartZonePos, 'body_start zone must come before body_end zone');
    }

    // --- Frontend-only rendering tests ---

    public function test_code_injection_is_not_in_admin_layout(): void
    {
        $filamentViewsPath = __DIR__ . '/../../resources/views/filament';
        $errors = [];

        if (! is_dir($filamentViewsPath)) {
            $this->markTestSkipped('Filament views directory not found');
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($filamentViewsPath)
        );

        foreach ($iterator as $file) {
            if ($file->isDir() || $file->getExtension() !== 'php') {
                continue;
            }

            $content = file_get_contents($file->getPathname());
            $relativePath = str_replace(__DIR__ . '/../../resources/views/', '', $file->getPathname());

            // The admin page form references code-injection as a page name, not as a component
            // Skip the code-injection admin page itself
            if (str_contains($relativePath, 'code-injection.blade.php')) {
                continue;
            }

            if (str_contains($content, '<x-tallcms::code-injection')) {
                $errors[] = "{$relativePath}: Contains code-injection component (should only be in frontend layout)";
            }
        }

        $this->assertEmpty(
            $errors,
            "Code injection component found in admin views:\n" . implode("\n", $errors)
        );
    }

    // --- Permission tests ---

    public function test_code_injection_page_class_exists(): void
    {
        $this->assertTrue(
            class_exists(\TallCms\Cms\Filament\Pages\CodeInjection::class),
            'CodeInjection page class must exist'
        );
    }

    public function test_code_injection_page_does_not_use_has_page_shield(): void
    {
        $reflection = new \ReflectionClass(\TallCms\Cms\Filament\Pages\CodeInjection::class);
        $traits = $reflection->getTraitNames();

        $this->assertNotContains(
            'BezhanSalleh\\FilamentShield\\Traits\\HasPageShield',
            $traits,
            'CodeInjection must NOT use HasPageShield trait'
        );
    }

    public function test_code_injection_page_has_manual_can_access(): void
    {
        $reflection = new \ReflectionClass(\TallCms\Cms\Filament\Pages\CodeInjection::class);

        $this->assertTrue(
            $reflection->hasMethod('canAccess'),
            'CodeInjection must implement canAccess()'
        );

        // Verify canAccess is declared in CodeInjection itself, not just inherited
        $method = $reflection->getMethod('canAccess');
        $this->assertEquals(
            \TallCms\Cms\Filament\Pages\CodeInjection::class,
            $method->getDeclaringClass()->getName(),
            'canAccess() must be declared in CodeInjection class'
        );
    }

    // --- Permission registration tests ---

    public function test_permission_is_in_service_provider_shield_config(): void
    {
        $serviceProviderPath = __DIR__ . '/../../src/TallCmsServiceProvider.php';
        $content = file_get_contents($serviceProviderPath);

        $this->assertStringContainsString(
            "'Manage:CodeInjection'",
            $content,
            'TallCmsServiceProvider must include Manage:CodeInjection in Shield custom permissions'
        );
    }

    public function test_permission_is_in_setup_command(): void
    {
        $setupPath = __DIR__ . '/../../src/Console/Commands/TallCmsSetup.php';
        $content = file_get_contents($setupPath);

        $this->assertStringContainsString(
            "'Manage:CodeInjection'",
            $content,
            'TallCmsSetup must include Manage:CodeInjection in custom permissions'
        );
    }

    public function test_administrator_role_includes_code_injection(): void
    {
        $setupPath = __DIR__ . '/../../src/Console/Commands/TallCmsSetup.php';
        $content = file_get_contents($setupPath);

        $this->assertStringContainsString(
            'codeinjection',
            $content,
            'TallCmsSetup isAdministratorPermission must check for codeinjection'
        );
    }

    // --- Plugin registration tests ---

    public function test_code_injection_page_is_registered_in_plugin(): void
    {
        $pluginPath = __DIR__ . '/../../src/TallCmsPlugin.php';
        $content = file_get_contents($pluginPath);

        $this->assertStringContainsString(
            'CodeInjection::class',
            $content,
            'TallCmsPlugin must register CodeInjection page'
        );
    }

    public function test_plugin_has_opt_out_method(): void
    {
        $reflection = new \ReflectionClass(\TallCms\Cms\TallCmsPlugin::class);

        $this->assertTrue(
            $reflection->hasMethod('withoutCodeInjection'),
            'TallCmsPlugin must have withoutCodeInjection() method'
        );
    }

    // --- Migration test ---

    public function test_migration_file_exists(): void
    {
        $migrationPath = __DIR__ . '/../../database/migrations/2026_03_04_000001_create_manage_code_injection_permission.php';

        $this->assertFileExists($migrationPath, 'Code injection permission migration must exist');
    }
}
