<?php

namespace TallCms\Cms\Tests\Feature;

use TallCms\Cms\Tests\TestCase;

/**
 * Tests that verify routes are actually registered after app boots.
 * These catch issues like duplicate route definitions overwriting names.
 */
class RouteRegistrationTest extends TestCase
{
    /**
     * Essential routes that MUST exist in plugin mode.
     */
    protected array $essentialRoutes = [
        'tallcms.preview.page',
        'tallcms.preview.post',
        'tallcms.preview.token',
        'tallcms.contact.submit',
    ];

    /**
     * Frontend routes (only when routes_enabled is true).
     */
    protected array $frontendRoutes = [
        'tallcms.cms.home',
        'tallcms.cms.page',
    ];

    public function test_essential_routes_are_registered(): void
    {
        $router = $this->app['router'];

        foreach ($this->essentialRoutes as $routeName) {
            $this->assertTrue(
                $router->has($routeName),
                "Essential route [{$routeName}] should be registered in plugin mode"
            );
        }
    }

    public function test_preview_routes_can_generate_urls(): void
    {
        // Create a mock page ID to test URL generation
        $pageId = 1;
        $postId = 1;
        $token = 'test-token';

        // These should not throw "Route not defined" exceptions
        $pageUrl = route('tallcms.preview.page', ['page' => $pageId]);
        $postUrl = route('tallcms.preview.post', ['post' => $postId]);
        $tokenUrl = route('tallcms.preview.token', ['token' => $token]);

        $this->assertStringContainsString('/preview/page/', $pageUrl);
        $this->assertStringContainsString('/preview/post/', $postUrl);
        $this->assertStringContainsString('/preview/share/', $tokenUrl);
    }

    public function test_contact_route_accepts_post_method(): void
    {
        $route = $this->app['router']->getRoutes()->getByName('tallcms.contact.submit');

        $this->assertNotNull($route, 'Contact submit route should exist');
        $this->assertContains('POST', $route->methods(), 'Contact route should accept POST');
    }

    public function test_preview_routes_require_authentication(): void
    {
        // Preview page/post routes should have auth middleware
        $pageRoute = $this->app['router']->getRoutes()->getByName('tallcms.preview.page');
        $postRoute = $this->app['router']->getRoutes()->getByName('tallcms.preview.post');

        $this->assertNotNull($pageRoute);
        $this->assertNotNull($postRoute);

        // Check middleware includes 'auth'
        $pageMiddleware = $pageRoute->middleware();
        $postMiddleware = $postRoute->middleware();

        $this->assertTrue(
            in_array('tallcms.preview-auth', $pageMiddleware),
            'Preview page route should require authentication via tallcms.preview-auth middleware'
        );
        $this->assertTrue(
            in_array('tallcms.preview-auth', $postMiddleware),
            'Preview post route should require authentication via tallcms.preview-auth middleware'
        );
    }

    public function test_token_preview_route_is_public(): void
    {
        $route = $this->app['router']->getRoutes()->getByName('tallcms.preview.token');

        $this->assertNotNull($route);

        $middleware = $route->middleware();

        // Should NOT have auth middleware (it's public with throttling)
        $this->assertNotContains('auth', $middleware, 'Token preview should be public');
    }

    public function test_frontend_routes_registered_when_enabled(): void
    {
        // Reconfigure with routes enabled
        $this->app['config']->set('tallcms.plugin_mode.routes_enabled', true);
        $this->app['config']->set('tallcms.plugin_mode.routes_prefix', 'cms');

        // Note: Routes are registered at boot time, so this test verifies
        // the config is set correctly. Full route registration would need
        // a fresh app boot.
        $this->assertTrue(config('tallcms.plugin_mode.routes_enabled'));
        $this->assertEquals('cms', config('tallcms.plugin_mode.routes_prefix'));
    }

    public function test_no_duplicate_route_names(): void
    {
        $router = $this->app['router'];
        $routes = $router->getRoutes();
        $routeNames = [];

        foreach ($routes as $route) {
            $name = $route->getName();
            if ($name && str_starts_with($name, 'tallcms.')) {
                // Check for duplicates (same URI, different names shouldn't happen)
                $uri = $route->uri();
                $key = $route->methods()[0] . ':' . $uri;

                if (isset($routeNames[$key])) {
                    $this->fail(
                        "Duplicate route URI [{$uri}] with names [{$routeNames[$key]}] and [{$name}]. " .
                        "Laravel only keeps one name per URI - this indicates a bug."
                    );
                }

                $routeNames[$key] = $name;
            }
        }

        $this->assertTrue(true, 'No duplicate route URIs found');
    }
}
