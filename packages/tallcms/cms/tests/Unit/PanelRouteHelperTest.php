<?php

namespace TallCms\Cms\Tests\Unit;

use Illuminate\Support\Facades\Route;
use TallCms\Cms\Tests\TestCase;

/**
 * Tests for tallcms_panel_route() helper function.
 *
 * Registers fake named routes to verify the helper builds correct
 * route names from the configured panel ID and passes parameters through.
 */
class PanelRouteHelperTest extends TestCase
{
    public function test_builds_route_with_default_panel_id(): void
    {
        $this->app['config']->set('tallcms.filament.panel_id', 'admin');

        Route::get('/admin/plugin-licenses', fn () => '')->name('filament.admin.pages.plugin-licenses');

        $url = tallcms_panel_route('pages.plugin-licenses');

        $this->assertStringEndsWith('/admin/plugin-licenses', $url);
    }

    public function test_builds_route_with_custom_panel_id(): void
    {
        $this->app['config']->set('tallcms.filament.panel_id', 'app');

        Route::get('/app/plugin-licenses', fn () => '')->name('filament.app.pages.plugin-licenses');

        $url = tallcms_panel_route('pages.plugin-licenses');

        $this->assertStringEndsWith('/app/plugin-licenses', $url);
    }

    public function test_passes_parameters_through(): void
    {
        $this->app['config']->set('tallcms.filament.panel_id', 'app');

        Route::get('/app/resources/cms-pages/{record}/edit', fn () => '')
            ->name('filament.app.resources.cms-pages.edit');

        $url = tallcms_panel_route('resources.cms-pages.edit', ['record' => 42]);

        $this->assertStringContains('/42/', $url);
    }

    public function test_passes_query_parameters(): void
    {
        $this->app['config']->set('tallcms.filament.panel_id', 'dashboard');

        Route::get('/dashboard/plugin-licenses', fn () => '')->name('filament.dashboard.pages.plugin-licenses');

        $url = tallcms_panel_route('pages.plugin-licenses', ['plugin' => 'tallcms-pro']);

        $this->assertStringContains('plugin=tallcms-pro', $url);
    }

    /**
     * Custom assertion: str_contains for URL parts.
     */
    protected static function assertStringContains(string $needle, string $haystack, string $message = ''): void
    {
        static::assertThat(
            str_contains($haystack, $needle),
            static::isTrue(),
            $message ?: "Failed asserting that '{$haystack}' contains '{$needle}'."
        );
    }
}
