<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use TallCms\Cms\Filament\Widgets\MenuOverviewWidget;
use Tests\TestCase;

/**
 * Regression test for the standalone dashboard crash.
 *
 * When the multisite plugin is NOT installed, content tables like tallcms_pages
 * and tallcms_menus have no site_id column. But the MenuOverviewWidget still
 * resolves a default site id (core creates a default Site row on install) and
 * previously blindly applied a `where('site_id', 1)` filter, producing
 *     SQLSTATE[42S22]: Column not found: 1054 Unknown column 'site_id'
 * on every dashboard load. The widget must gate every site_id filter on
 * Schema::hasColumn().
 */
class MenuOverviewWidgetSingleSiteTest extends TestCase
{
    use RefreshDatabase;

    public function test_widget_does_not_apply_site_id_filter_when_column_is_missing(): void
    {
        // Mock Schema::hasColumn to simulate single-site install where the
        // multisite plugin hasn't added site_id to content tables. SQLite
        // doesn't let us actually drop the column cleanly from within a test,
        // so we intercept the schema check instead.
        Schema::shouldReceive('hasColumn')
            ->with('tallcms_pages', 'site_id')
            ->andReturn(false);
        Schema::shouldReceive('hasColumn')
            ->with('tallcms_menus', 'site_id')
            ->andReturn(false);

        // The core migration creates a default site; confirm it's there so
        // getMultisiteSiteId() will return a non-null id and we actually
        // exercise the guard rather than trivially skipping it.
        $defaultSiteId = DB::table('tallcms_sites')->where('is_default', true)->value('id');
        $this->assertNotNull($defaultSiteId);

        $user = \App\Models\User::factory()->create();
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $user->assignRole('super_admin');
        $this->actingAs($user);

        $widget = new MenuOverviewWidget;

        $reflection = new \ReflectionMethod($widget, 'getStats');
        $reflection->setAccessible(true);

        // Before the fix, this threw QueryException on "Unknown column 'site_id'".
        $stats = $reflection->invoke($widget);

        $this->assertIsArray($stats);
        $this->assertCount(3, $stats);
    }

    public function test_widget_applies_site_id_filter_when_column_exists(): void
    {
        // Real schema in this test suite has the site_id column (multisite
        // plugin migrations run). Insert content across two sites and confirm
        // the widget's counts are scoped to the admin-selected site.
        $siteA = (int) DB::table('tallcms_sites')->where('is_default', true)->value('id');
        $siteB = (int) DB::table('tallcms_sites')->insertGetId([
            'name' => 'Site B',
            'domain' => 'site-b.test',
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'is_default' => false,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->insertPage($siteA, 'A One');
        $this->insertPage($siteA, 'A Two');
        $this->insertPage($siteB, 'B One');

        $user = \App\Models\User::factory()->create();
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $user->assignRole('super_admin');
        $this->actingAs($user);
        session(['multisite_admin_site_id' => $siteA]);

        $widget = new MenuOverviewWidget;
        $reflection = new \ReflectionMethod($widget, 'getStats');
        $reflection->setAccessible(true);
        $stats = $reflection->invoke($widget);

        // First stat is "Pages" — should reflect siteA's 2 pages, not all 3.
        $this->assertSame(2, $this->extractStatValue($stats[0]));
    }

    protected function insertPage(int $siteId, string $title): void
    {
        DB::table('tallcms_pages')->insert([
            'site_id' => $siteId,
            'title' => json_encode(['en' => $title]),
            'slug' => json_encode(['en' => \Illuminate\Support\Str::slug($title)]),
            'status' => 'published',
            'published_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function extractStatValue(\Filament\Widgets\StatsOverviewWidget\Stat $stat): int
    {
        $reflection = new \ReflectionProperty($stat, 'value');
        $reflection->setAccessible(true);

        return (int) $reflection->getValue($stat);
    }
}
