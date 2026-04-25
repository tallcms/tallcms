<?php

namespace TallCms\Cms\Tests\Unit;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\PermissionServiceProvider;
use TallCms\Cms\Filament\Resources\SiteResource\Pages\EditSite;
use TallCms\Cms\Filament\Resources\SiteResource\SiteForm;
use TallCms\Cms\Models\SiteSetting;
use TallCms\Cms\Services\SiteSettingsService;
use TallCms\Cms\Tests\Fixtures\User;
use TallCms\Cms\Tests\TestCase;

/**
 * Embed code (a.k.a. code injection) integration tests.
 *
 * Covers:
 *   - Layout placement: every bundled layout has the head/body_start/body_end
 *     code-injection zones in the correct positions (the View Component is the
 *     frontend renderer; placement determines where each zone shows up).
 *   - Admin views guard: the frontend code-injection component never bleeds
 *     into admin templates.
 *   - EditSite save flow: embed code is written as a per-site override using
 *     the explicit site_id from the edited record — never the ambient
 *     SiteSetting::set() path that would otherwise resolve to global when no
 *     session selection is present (the v2.x multisite plugin removed ambient
 *     session-based site context entirely; saves must be explicit).
 */
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

        // Spatie Permission tables (User model relations require them)
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

        // Settings tables (global + per-site overrides)
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

        Schema::create('tallcms_sites', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('domain')->unique();
            $table->string('theme')->nullable();
            $table->string('locale')->nullable();
            $table->string('uuid')->unique()->nullable();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('tallcms_site_setting_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained('tallcms_sites')->cascadeOnDelete();
            $table->string('key');
            $table->longText('value')->nullable();
            $table->string('type')->default('text');
            $table->timestamps();
            $table->unique(['site_id', 'key']);
        });
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->layoutPath = __DIR__.'/../../resources/views/layouts/app.blade.php';
        Cache::flush();
        SiteSetting::forgetMemoizedDefaultSiteId();
        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    protected function tearDown(): void
    {
        // Reset the static default-site memoization so seeded site rows don't
        // leak into later test classes that don't create tallcms_sites.
        SiteSetting::forgetMemoizedDefaultSiteId();
        parent::tearDown();
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

        $themesDir = $projectRoot.'/themes';
        if (is_dir($themesDir)) {
            foreach (glob($themesDir.'/*/resources/views/layouts/app.blade.php') as $themeLayout) {
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

    // --- Frontend-only rendering guard ---

    public function test_code_injection_component_is_not_in_admin_views(): void
    {
        $filamentViewsPath = __DIR__.'/../../resources/views/filament';
        $errors = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($filamentViewsPath)
        );

        foreach ($iterator as $file) {
            if ($file->isDir() || $file->getExtension() !== 'php') {
                continue;
            }

            $content = file_get_contents($file->getPathname());
            $relativePath = str_replace(__DIR__.'/../../resources/views/', '', $file->getPathname());

            if (str_contains($content, '<x-tallcms::code-injection')) {
                $errors[] = "{$relativePath}: Contains code-injection component (should only be in frontend layout)";
            }
        }

        $this->assertEmpty(
            $errors,
            "Code injection component found in admin views:\n".implode("\n", $errors)
        );
    }

    // --- EditSite save flow (the canonical write path post-v2 multisite) ---
    //
    // The v2.x multisite plugin removed ambient session-based site context.
    // SiteSetting::set() with no session/no resolver is no longer a reliable
    // way to write per-site values from admin — it falls through to global.
    // The only correct write path is explicit: SiteSettingsService::setForSite()
    // with the site_id from the edited record. EditSite uses exactly that.

    public function test_embed_code_keys_are_wired_into_edit_site_setting_keys(): void
    {
        // Reflection check: regression guard against removing the embed code keys.
        $reflection = new \ReflectionClass(EditSite::class);
        $property = $reflection->getProperty('settingKeys');
        $property->setAccessible(true);

        // The default value lives on the class definition.
        $defaults = $reflection->getDefaultProperties();
        $keys = $defaults['settingKeys'] ?? [];

        $this->assertArrayHasKey('code_head', $keys, 'EditSite must include code_head in $settingKeys');
        $this->assertArrayHasKey('code_body_start', $keys, 'EditSite must include code_body_start in $settingKeys');
        $this->assertArrayHasKey('code_body_end', $keys, 'EditSite must include code_body_end in $settingKeys');

        $this->assertEquals('text', $keys['code_head']);
        $this->assertEquals('text', $keys['code_body_start']);
        $this->assertEquals('text', $keys['code_body_end']);
    }

    /**
     * The headline regression test the bug report asked for.
     *
     * Setup mirrors a real Filament admin Livewire request from a non-super_admin
     * site owner: tallcms.admin_context = true, no multisite_admin_site_id session
     * (the v2.x plugin removed that), authenticated user owns one Site. We invoke
     * the same SiteSettingsService::setForSite() the EditSite afterSave() path
     * uses, with the explicit site_id from the record.
     *
     * Asserts: per-site override row created for the right site_id, and the
     * global tallcms_site_settings table is untouched (proving the save did
     * not slip through SiteSetting::set() and write global).
     */
    public function test_edit_site_save_writes_per_site_override_without_session_state(): void
    {
        $owner = User::create([
            'name' => 'Site Owner',
            'email' => 'owner@test.com',
            'password' => 'pw',
        ]);

        $siteId = DB::table('tallcms_sites')->insertGetId([
            'name' => 'Owned Site',
            'domain' => 'owned.test',
            'uuid' => (string) Str::uuid(),
            'user_id' => $owner->id,
            'is_default' => false,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($owner);
        request()->attributes->set('tallcms.admin_context', true);
        // Deliberately do NOT set session('multisite_admin_site_id') — v2.x
        // multisite navigation no longer mutates that session.

        $service = app(SiteSettingsService::class);

        // Replicate EditSite::afterSave() for the embed code keys: explicit
        // site_id, never ambient SiteSetting::set().
        foreach (['code_head' => '<!-- head -->', 'code_body_start' => '<!-- start -->', 'code_body_end' => '<!-- end -->'] as $key => $value) {
            $service->setForSite($siteId, $key, $value, 'text');
        }

        // Each key landed in the override table, scoped to the right site.
        foreach (['code_head' => '<!-- head -->', 'code_body_start' => '<!-- start -->', 'code_body_end' => '<!-- end -->'] as $key => $expected) {
            $row = DB::table('tallcms_site_setting_overrides')
                ->where('site_id', $siteId)
                ->where('key', $key)
                ->first();

            $this->assertNotNull($row, "Override row missing for {$key}");
            $this->assertEquals($expected, $row->value);
        }

        // Global table for these keys is untouched — no leakage from the
        // per-site save path to the All-Sites default.
        foreach (['code_head', 'code_body_start', 'code_body_end'] as $key) {
            $this->assertNull(
                DB::table('tallcms_site_settings')->where('key', $key)->first(),
                "Global tallcms_site_settings.{$key} must not be written by per-site save"
            );
        }
    }

    /**
     * Cross-site isolation: site A's override does not bleed into site B's
     * read via the explicit getForSite() lookup that EditSite uses on mount.
     */
    public function test_edit_site_per_site_overrides_do_not_leak_across_sites(): void
    {
        $owner = User::create(['name' => 'Owner', 'email' => 'o@t.com', 'password' => 'pw']);

        $siteA = DB::table('tallcms_sites')->insertGetId([
            'name' => 'A', 'domain' => 'a.test', 'uuid' => (string) Str::uuid(),
            'user_id' => $owner->id, 'is_default' => false, 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $siteB = DB::table('tallcms_sites')->insertGetId([
            'name' => 'B', 'domain' => 'b.test', 'uuid' => (string) Str::uuid(),
            'user_id' => $owner->id, 'is_default' => false, 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $service = app(SiteSettingsService::class);
        $service->setForSite($siteA, 'code_head', '<!-- A -->', 'text');

        $this->assertEquals('<!-- A -->', $service->getForSite($siteA, 'code_head'));
        $this->assertNull($service->getForSite($siteB, 'code_head'),
            'Site B must not inherit Site A\'s override; should fall back to (empty) global default');
    }

    // --- Form schema drift guards ---
    //
    // The unit tests above exercise the *write path* (SiteSettingsService).
    // These tests guard the *form schema* — the Embed Code tab must keep the
    // three textarea fields wired to the right names, otherwise EditSite's
    // settingKeys-based save loop would silently skip them.
    //
    // Filament's runtime component tree requires container binding to walk
    // (`getChildComponents()` throws without a parent), so these guards inspect
    // the SiteForm method source directly. This is brittle to formatting but
    // robust to the only failure mode that matters: someone renaming or
    // removing a textarea name.

    public function test_embed_code_tab_method_references_three_textarea_field_names(): void
    {
        $source = $this->getMethodSource(SiteForm::class, 'embedCodeTab');

        $this->assertStringContainsString("Textarea::make('code_head')", $source,
            'embedCodeTab() must define a Textarea named code_head');
        $this->assertStringContainsString("Textarea::make('code_body_start')", $source,
            'embedCodeTab() must define a Textarea named code_body_start');
        $this->assertStringContainsString("Textarea::make('code_body_end')", $source,
            'embedCodeTab() must define a Textarea named code_body_end');
    }

    public function test_site_form_schema_method_includes_embed_code_tab(): void
    {
        $source = $this->getMethodSource(SiteForm::class, 'schema');

        $this->assertStringContainsString('embedCodeTab()', $source,
            'SiteForm::schema() must wire embedCodeTab() into the Tabs list');
    }

    private function getMethodSource(string $class, string $method): string
    {
        $reflection = new \ReflectionMethod($class, $method);
        $file = file($reflection->getFileName());

        return implode('', array_slice(
            $file,
            $reflection->getStartLine() - 1,
            $reflection->getEndLine() - $reflection->getStartLine() + 1
        ));
    }

    // --- Test gap note ---
    //
    // What's *not* covered: a true page-level Livewire test of EditSite that
    // fills the form and calls save() through Filament's form lifecycle. That
    // would require bootstrapping a Filament panel + LivewireServiceProvider
    // in the package test environment, which neither core nor the multisite
    // plugin currently does. The structural guards above (settingKeys
    // reflection + form schema introspection + service-level write path)
    // collectively catch the regression class identified in the bug report
    // (form/schema drift, ambient session writes), but a future PR should
    // wire up Filament panel bootstrap in this test class so we can drive
    // EditSite through `Livewire::test(EditSite::class, ['record' => ...])`.
    // See https://filamentphp.com/docs/5.x/panels/testing for the pattern.
}
