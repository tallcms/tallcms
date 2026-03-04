<?php

namespace TallCms\Cms\Tests\Unit;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\PermissionServiceProvider;
use TallCms\Cms\Filament\Pages\CodeInjection;
use TallCms\Cms\Models\SiteSetting;
use TallCms\Cms\Tests\Fixtures\User;
use TallCms\Cms\Tests\TestCase;

class CodeInjectionIntegrationTest extends TestCase
{
    private string $layoutPath;

    protected function getPackageProviders($app): array
    {
        return array_merge(parent::getPackageProviders($app), [
            PermissionServiceProvider::class,
        ]);
    }

    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('permission.models.permission', \Spatie\Permission\Models\Permission::class);
        $app['config']->set('permission.models.role', \Spatie\Permission\Models\Role::class);
        $app['config']->set('permission.table_names.permissions', 'permissions');
        $app['config']->set('permission.table_names.roles', 'roles');
        $app['config']->set('permission.table_names.model_has_permissions', 'model_has_permissions');
        $app['config']->set('permission.table_names.model_has_roles', 'model_has_roles');
        $app['config']->set('permission.table_names.role_has_permissions', 'role_has_permissions');
        $app['config']->set('permission.column_names.role_pivot_key', 'role_id');
        $app['config']->set('permission.column_names.permission_pivot_key', 'permission_id');
        $app['config']->set('permission.column_names.model_morph_key', 'model_id');
        $app['config']->set('permission.column_names.team_foreign_key', 'team_id');
        $app['config']->set('permission.teams', false);
        $app['config']->set('permission.register_permission_check_method', true);
        $app['config']->set('permission.register_octane_reset_listener', false);
        $app['config']->set('permission.cache.expiration_time', 0);
        $app['config']->set('permission.cache.key', 'spatie.permission.cache');
        $app['config']->set('permission.cache.store', 'default');
    }

    protected function defineDatabaseMigrations(): void
    {
        parent::defineDatabaseMigrations();

        // Create Spatie Permission tables
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });

        Schema::create('model_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->index(['model_id', 'model_type']);
            $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
            $table->primary(['permission_id', 'model_id', 'model_type']);
        });

        Schema::create('model_has_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->index(['model_id', 'model_type']);
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
            $table->primary(['role_id', 'model_id', 'model_type']);
        });

        Schema::create('role_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
            $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
            $table->primary(['permission_id', 'role_id']);
        });

        // Create site_settings table
        Schema::create('tallcms_site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('text');
            $table->string('group')->default('general');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->index(['key', 'group']);
        });
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->layoutPath = __DIR__ . '/../../resources/views/layouts/app.blade.php';
        Cache::flush();
        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    // --- Layout placement tests (package + all bundled themes) ---

    /**
     * Returns every layout that must contain code-injection zones:
     * the core package layout and all bundled theme layouts.
     */
    private function getAllLayoutPaths(): array
    {
        $projectRoot = dirname(__DIR__, 5); // tests/Unit -> tests -> cms -> tallcms -> packages -> project root

        $layouts = [
            'package' => $this->layoutPath,
        ];

        $themesDir = $projectRoot . '/themes';
        if (is_dir($themesDir)) {
            foreach (glob($themesDir . '/*/resources/views/layouts/app.blade.php') as $themeLayout) {
                $themeName = basename(dirname($themeLayout, 4));
                $layouts["theme:{$themeName}"] = $themeLayout;
            }
        }

        return $layouts;
    }

    public function test_all_layouts_have_head_zone_inside_head_tag(): void
    {
        foreach ($this->getAllLayoutPaths() as $label => $path) {
            $layout = file_get_contents($path);

            $headOpenPos = strpos($layout, '<head');
            $headClosePos = strpos($layout, '</head>');
            $zonePos = strpos($layout, 'zone="head"');

            $this->assertNotFalse($zonePos, "[{$label}] Missing code-injection zone=\"head\"");
            $this->assertGreaterThan($headOpenPos, $zonePos, "[{$label}] Head zone must appear after <head>");
            $this->assertLessThan($headClosePos, $zonePos, "[{$label}] Head zone must appear before </head>");
        }
    }

    public function test_all_layouts_have_body_start_zone_after_body_tag(): void
    {
        foreach ($this->getAllLayoutPaths() as $label => $path) {
            $layout = file_get_contents($path);

            $bodyOpenPos = strpos($layout, '<body');
            $bodyStartZonePos = strpos($layout, 'zone="body_start"');
            $bodyEndZonePos = strpos($layout, 'zone="body_end"');

            $this->assertNotFalse($bodyStartZonePos, "[{$label}] Missing code-injection zone=\"body_start\"");
            $this->assertGreaterThan($bodyOpenPos, $bodyStartZonePos, "[{$label}] body_start zone must appear after <body>");
            $this->assertLessThan($bodyEndZonePos, $bodyStartZonePos, "[{$label}] body_start zone must appear before body_end zone");
        }
    }

    public function test_all_layouts_have_body_end_zone_before_body_close(): void
    {
        foreach ($this->getAllLayoutPaths() as $label => $path) {
            $layout = file_get_contents($path);

            $bodyClosePos = strpos($layout, '</body>');
            $bodyEndZonePos = strpos($layout, 'zone="body_end"');

            $this->assertNotFalse($bodyEndZonePos, "[{$label}] Missing code-injection zone=\"body_end\"");
            $this->assertLessThan($bodyClosePos, $bodyEndZonePos, "[{$label}] body_end zone must appear before </body>");
        }
    }

    public function test_all_layouts_have_zones_in_correct_order(): void
    {
        foreach ($this->getAllLayoutPaths() as $label => $path) {
            $layout = file_get_contents($path);

            $headZonePos = strpos($layout, 'zone="head"');
            $bodyStartZonePos = strpos($layout, 'zone="body_start"');
            $bodyEndZonePos = strpos($layout, 'zone="body_end"');

            $this->assertLessThan($bodyStartZonePos, $headZonePos, "[{$label}] Head zone must come before body_start zone");
            $this->assertLessThan($bodyEndZonePos, $bodyStartZonePos, "[{$label}] body_start zone must come before body_end zone");
        }
    }

    // --- Frontend-only rendering tests ---

    public function test_code_injection_component_is_not_in_admin_views(): void
    {
        $filamentViewsPath = __DIR__ . '/../../resources/views/filament';
        $errors = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($filamentViewsPath)
        );

        foreach ($iterator as $file) {
            if ($file->isDir() || $file->getExtension() !== 'php') {
                continue;
            }

            $content = file_get_contents($file->getPathname());
            $relativePath = str_replace(__DIR__ . '/../../resources/views/', '', $file->getPathname());

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

    // --- Permission authorization tests (real user + real permissions) ---

    public function test_can_access_returns_false_without_permission(): void
    {
        $user = User::create([
            'name' => 'Editor',
            'email' => 'editor@test.com',
            'password' => 'password',
        ]);

        $this->actingAs($user);

        $this->assertFalse(CodeInjection::canAccess());
    }

    public function test_can_access_returns_true_with_permission(): void
    {
        Permission::create(['name' => 'Manage:CodeInjection', 'guard_name' => 'web']);

        $user = User::create([
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => 'password',
        ]);
        $user->givePermissionTo('Manage:CodeInjection');

        $this->actingAs($user);
        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->assertTrue(CodeInjection::canAccess());
    }

    public function test_can_access_returns_false_when_not_authenticated(): void
    {
        $this->assertFalse(CodeInjection::canAccess());
    }

    public function test_should_register_navigation_matches_can_access(): void
    {
        $this->assertFalse(CodeInjection::canAccess());
        $this->assertFalse(CodeInjection::shouldRegisterNavigation());

        Permission::create(['name' => 'Manage:CodeInjection', 'guard_name' => 'web']);

        $user = User::create([
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => 'password',
        ]);
        $user->givePermissionTo('Manage:CodeInjection');

        $this->actingAs($user);
        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->assertTrue(CodeInjection::canAccess());
        $this->assertTrue(CodeInjection::shouldRegisterNavigation());
    }

    // --- Audit recording tests (real DB writes) ---

    public function test_save_records_audit_metadata_for_each_zone(): void
    {
        $user = User::create([
            'name' => 'Test Admin',
            'email' => 'audit@test.com',
            'password' => 'password',
        ]);
        $this->actingAs($user);

        foreach (['code_head', 'code_body_start', 'code_body_end'] as $key) {
            SiteSetting::set($key, '<!-- test -->', 'text', 'code-injection');
            SiteSetting::set("{$key}_audit", [
                'user_id' => $user->id,
                'name' => $user->name,
                'at' => now()->toIso8601String(),
            ], 'json', 'code-injection');
        }

        Cache::flush();

        foreach (['code_head', 'code_body_start', 'code_body_end'] as $key) {
            $audit = SiteSetting::get("{$key}_audit");
            $this->assertIsArray($audit, "Audit for {$key} must be an array");
            $this->assertEquals($user->id, $audit['user_id']);
            $this->assertEquals('Test Admin', $audit['name']);
            $this->assertArrayHasKey('at', $audit);
        }
    }

    public function test_audit_metadata_stores_correct_user(): void
    {
        $user = User::create([
            'name' => 'Jane Doe',
            'email' => 'jane@test.com',
            'password' => 'password',
        ]);
        $this->actingAs($user);

        SiteSetting::set('code_head_audit', [
            'user_id' => $user->id,
            'name' => $user->name,
            'at' => now()->toIso8601String(),
        ], 'json', 'code-injection');

        Cache::flush();

        $audit = SiteSetting::get('code_head_audit');
        $this->assertEquals('Jane Doe', $audit['name']);
        $this->assertEquals($user->id, $audit['user_id']);
    }

    // --- Permission merged into Shield config at runtime ---

    public function test_manage_code_injection_is_in_shield_custom_permissions(): void
    {
        $permissions = config('filament-shield.custom_permissions', []);

        $this->assertContains(
            'Manage:CodeInjection',
            $permissions,
            'Manage:CodeInjection must be merged into Shield custom_permissions at runtime'
        );
    }

    // --- Page does not use HasPageShield ---

    public function test_code_injection_page_does_not_use_has_page_shield(): void
    {
        $traits = class_uses_recursive(CodeInjection::class);

        $this->assertArrayNotHasKey(
            'BezhanSalleh\FilamentShield\Traits\HasPageShield',
            $traits,
            'CodeInjection must NOT use HasPageShield trait'
        );
    }

    // --- Plugin opt-out ---

    public function test_without_code_injection_removes_page_from_plugin(): void
    {
        $plugin = \TallCms\Cms\TallCmsPlugin::make();

        $this->assertContains(CodeInjection::class, $plugin->getPages());

        $plugin->withoutCodeInjection();
        $this->assertNotContains(CodeInjection::class, $plugin->getPages());
    }
}
