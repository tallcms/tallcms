<?php

namespace Tests\Feature;

use App\Models\Plugin;
use App\Providers\PluginServiceProvider;
use App\Services\PluginValidator;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Tests for plugin security guardrails - route registration bypass prevention
 *
 * These tests verify that the regex patterns correctly block malicious route
 * registration attempts while allowing legitimate plugin code.
 */
class PluginSecurityGuardrailsTest extends TestCase
{
    protected PluginServiceProvider $serviceProvider;

    protected PluginValidator $validator;

    protected string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->serviceProvider = new PluginServiceProvider($this->app);
        $this->validator = new PluginValidator;
        $this->tempDir = storage_path('framework/testing/plugin-guardrails');

        if (! File::exists($this->tempDir)) {
            File::makeDirectory($this->tempDir, 0755, true);
        }
    }

    protected function tearDown(): void
    {
        if (File::exists($this->tempDir)) {
            File::deleteDirectory($this->tempDir);
        }

        parent::tearDown();
    }

    /**
     * Helper to invoke protected parseRouteFile method
     */
    protected function parseRouteFile(string $content): array
    {
        $path = $this->tempDir.'/test-route.php';
        File::put($path, $content);

        $reflection = new \ReflectionClass($this->serviceProvider);
        $method = $reflection->getMethod('parseRouteFile');
        $method->setAccessible(true);

        return $method->invoke($this->serviceProvider, $path);
    }

    /**
     * Helper to test src file scanning
     */
    protected function scanSrcForRouterUsage(string $content): array
    {
        $srcPath = $this->tempDir.'/src';
        if (! File::exists($srcPath)) {
            File::makeDirectory($srcPath, 0755, true);
        }

        File::put($srcPath.'/TestClass.php', $content);

        return $this->validator->scanSrcForRouterUsage($srcPath);
    }

    // =========================================================================
    // LEGITIMATE PATTERNS - SHOULD BE ALLOWED
    // =========================================================================

    public function test_allows_simple_route_get(): void
    {
        $result = $this->parseRouteFile("<?php\nRoute::get('/hello', fn() => 'world');");

        $this->assertTrue($result['valid']);
        $this->assertContains('/hello', $result['routes']);
    }

    public function test_allows_simple_route_post(): void
    {
        $result = $this->parseRouteFile("<?php\nRoute::post('/submit', fn() => 'ok');");

        $this->assertTrue($result['valid']);
        $this->assertContains('/submit', $result['routes']);
    }

    public function test_allows_multiple_routes(): void
    {
        $result = $this->parseRouteFile("<?php
Route::get('/one', fn() => '1');
Route::post('/two', fn() => '2');
Route::put('/three', fn() => '3');
");

        $this->assertTrue($result['valid']);
        $this->assertCount(3, $result['routes']);
    }

    public function test_allows_route_with_chained_name(): void
    {
        $result = $this->parseRouteFile("<?php
Route::get('/hello', fn() => 'world')->name('hello');
");

        $this->assertTrue($result['valid']);
        $this->assertContains('/hello', $result['routes']);
    }

    // =========================================================================
    // ROUTE:: CASE VARIATIONS - SHOULD BE PARSED CORRECTLY
    // (PHP class names are case-insensitive, so route:: = Route:: = ROUTE::)
    // =========================================================================

    public function test_parses_lowercase_route(): void
    {
        $result = $this->parseRouteFile("<?php\nroute::get('/hello', fn() => 'ok');");

        // Case-insensitive parsing should extract the route
        $this->assertTrue($result['valid']);
        $this->assertContains('/hello', $result['routes']);
    }

    public function test_parses_uppercase_route(): void
    {
        $result = $this->parseRouteFile("<?php\nROUTE::get('/hello', fn() => 'ok');");

        $this->assertTrue($result['valid']);
        $this->assertContains('/hello', $result['routes']);
    }

    public function test_parses_mixed_case_route(): void
    {
        $result = $this->parseRouteFile("<?php\nRoUtE::get('/hello', fn() => 'ok');");

        $this->assertTrue($result['valid']);
        $this->assertContains('/hello', $result['routes']);
    }

    // =========================================================================
    // FORBIDDEN METHODS WITH CASE VARIATIONS - SHOULD BE BLOCKED
    // =========================================================================

    public function test_blocks_lowercase_route_any(): void
    {
        $result = $this->parseRouteFile("<?php\nroute::any('/bypass', fn() => 'bad');");

        $this->assertFalse($result['valid']);
    }

    public function test_blocks_uppercase_route_group(): void
    {
        $result = $this->parseRouteFile("<?php\nROUTE::group([], fn() => null);");

        $this->assertFalse($result['valid']);
    }

    // =========================================================================
    // ALIASED ROUTE FACADE - SHOULD BE BLOCKED
    // =========================================================================

    public function test_blocks_aliased_route_facade(): void
    {
        $result = $this->parseRouteFile("<?php
use Illuminate\Support\Facades\Route as R;
R::get('/bypass', fn() => 'bad');
");

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('alias', strtolower($result['error']));
    }

    public function test_blocks_aliased_route_facade_lowercase_usage(): void
    {
        $result = $this->parseRouteFile("<?php
use Illuminate\Support\Facades\Route as R;
r::get('/bypass', fn() => 'bad');
");

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('alias', strtolower($result['error']));
    }

    public function test_blocks_aliased_route_facade_mixed_case(): void
    {
        $result = $this->parseRouteFile("<?php
use Illuminate\\Support\\Facades\\Route as MyRoute;
myroute::get('/bypass', fn() => 'bad');
");

        $this->assertFalse($result['valid']);
    }

    // =========================================================================
    // APP('ROUTER') PATTERNS - SHOULD BE BLOCKED
    // =========================================================================

    public function test_blocks_app_router(): void
    {
        $result = $this->parseRouteFile("<?php\n\$router = app('router');\n\$router->get('/bypass', fn() => 'bad');");

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('router', strtolower($result['error']));
    }

    public function test_blocks_app_router_uppercase(): void
    {
        $result = $this->parseRouteFile("<?php\n\$router = APP('router');\n\$router->get('/bypass', fn() => 'bad');");

        $this->assertFalse($result['valid']);
    }

    public function test_blocks_app_router_mixed_case(): void
    {
        $result = $this->parseRouteFile("<?php\n\$router = App('router');\n\$router->get('/bypass', fn() => 'bad');");

        $this->assertFalse($result['valid']);
    }

    public function test_blocks_resolve_router(): void
    {
        $result = $this->parseRouteFile("<?php\n\$router = resolve('router');\n\$router->get('/bypass', fn() => 'bad');");

        $this->assertFalse($result['valid']);
    }

    public function test_blocks_resolve_router_uppercase(): void
    {
        $result = $this->parseRouteFile("<?php\n\$router = RESOLVE('router');\n\$router->get('/bypass', fn() => 'bad');");

        $this->assertFalse($result['valid']);
    }

    // =========================================================================
    // CONTAINER ARRAY ACCESS - SHOULD BE BLOCKED
    // =========================================================================

    public function test_blocks_app_array_access_router(): void
    {
        $result = $this->parseRouteFile("<?php\n\$router = app()['router'];\n\$router->get('/bypass', fn() => 'bad');");

        $this->assertFalse($result['valid']);
    }

    public function test_blocks_this_app_array_access_router(): void
    {
        $result = $this->parseRouteFile("<?php\n\$router = \$this->app['router'];\n\$router->get('/bypass', fn() => 'bad');");

        $this->assertFalse($result['valid']);
    }

    public function test_blocks_app_make_router(): void
    {
        $result = $this->parseRouteFile("<?php\n\$router = app()->make('router');\n\$router->get('/bypass', fn() => 'bad');");

        $this->assertFalse($result['valid']);
    }

    public function test_blocks_this_app_make_router(): void
    {
        $result = $this->parseRouteFile("<?php\n\$router = \$this->app->make('router');\n\$router->get('/bypass', fn() => 'bad');");

        $this->assertFalse($result['valid']);
    }

    public function test_blocks_app_facade_make_router(): void
    {
        $result = $this->parseRouteFile("<?php\n\$router = App::make('router');\n\$router->get('/bypass', fn() => 'bad');");

        $this->assertFalse($result['valid']);
    }

    // =========================================================================
    // ROUTER CLASS IMPORTS - SHOULD BE BLOCKED
    // =========================================================================

    public function test_blocks_router_class_import(): void
    {
        $result = $this->parseRouteFile('<?php
use Illuminate\\Routing\\Router;
// some code
');

        $this->assertFalse($result['valid']);
    }

    public function test_blocks_router_class_fqcn(): void
    {
        $result = $this->parseRouteFile('<?php
$router = new \\Illuminate\\Routing\\Router($events);
');

        $this->assertFalse($result['valid']);
    }

    public function test_blocks_registrar_contract(): void
    {
        $result = $this->parseRouteFile('<?php
use Illuminate\\Contracts\\Routing\\Registrar;
');

        $this->assertFalse($result['valid']);
    }

    // =========================================================================
    // DYNAMIC DISPATCH - SHOULD BE BLOCKED
    // =========================================================================

    public function test_blocks_route_class_constant(): void
    {
        $result = $this->parseRouteFile("<?php
\$class = Route::class;
\$class::get('/bypass', fn() => 'bad');
");

        $this->assertFalse($result['valid']);
    }

    public function test_blocks_route_class_constant_lowercase(): void
    {
        $result = $this->parseRouteFile("<?php
\$class = route::class;
\$class::get('/bypass', fn() => 'bad');
");

        $this->assertFalse($result['valid']);
    }

    public function test_blocks_router_class_constant(): void
    {
        $result = $this->parseRouteFile('<?php
$router = app(Router::class);
');

        $this->assertFalse($result['valid']);
    }

    public function test_blocks_registrar_class_constant(): void
    {
        $result = $this->parseRouteFile('<?php
$router = app(Registrar::class);
');

        $this->assertFalse($result['valid']);
    }

    public function test_blocks_call_user_func_with_route(): void
    {
        $result = $this->parseRouteFile("<?php
call_user_func([Route::class, 'get'], '/bypass', fn() => 'bad');
");

        $this->assertFalse($result['valid']);
    }

    public function test_blocks_call_user_func_array_with_route(): void
    {
        $result = $this->parseRouteFile("<?php
call_user_func_array([Route::class, 'get'], ['/bypass', fn() => 'bad']);
");

        $this->assertFalse($result['valid']);
    }

    public function test_blocks_route_facade_class_string(): void
    {
        $result = $this->parseRouteFile("<?php
\$class = 'Illuminate\\Support\\Facades\\Route';
\$class::get('/bypass', fn() => 'bad');
");

        $this->assertFalse($result['valid']);
    }

    public function test_blocks_variable_static_call(): void
    {
        $result = $this->parseRouteFile("<?php
\$class = getRouteClass();
\$class::get('/bypass', fn() => 'bad');
");

        $this->assertFalse($result['valid']);
    }

    // =========================================================================
    // FORBIDDEN ROUTE METHODS - SHOULD BE BLOCKED
    // =========================================================================

    public function test_blocks_route_any(): void
    {
        $result = $this->parseRouteFile("<?php\nRoute::any('/bypass', fn() => 'bad');");

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('any', strtolower($result['error']));
    }

    public function test_blocks_route_match(): void
    {
        $result = $this->parseRouteFile("<?php\nRoute::match(['get', 'post'], '/bypass', fn() => 'bad');");

        $this->assertFalse($result['valid']);
    }

    public function test_blocks_route_resource(): void
    {
        $result = $this->parseRouteFile("<?php\nRoute::resource('photos', PhotoController::class);");

        $this->assertFalse($result['valid']);
    }

    public function test_blocks_route_group(): void
    {
        $result = $this->parseRouteFile("<?php\nRoute::group([], function() { });");

        $this->assertFalse($result['valid']);
    }

    public function test_blocks_route_middleware_chained_to_group(): void
    {
        $result = $this->parseRouteFile("<?php\nRoute::middleware('auth')->group(function() { });");

        $this->assertFalse($result['valid']);
    }

    public function test_blocks_route_view(): void
    {
        $result = $this->parseRouteFile("<?php\nRoute::view('/welcome', 'welcome');");

        $this->assertFalse($result['valid']);
    }

    public function test_blocks_route_redirect(): void
    {
        $result = $this->parseRouteFile("<?php\nRoute::redirect('/here', '/there');");

        $this->assertFalse($result['valid']);
    }

    // =========================================================================
    // FILE INCLUSION - SHOULD BE BLOCKED
    // =========================================================================

    public function test_blocks_require(): void
    {
        $result = $this->parseRouteFile("<?php\nrequire 'other-routes.php';");

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('require', strtolower($result['error']));
    }

    public function test_blocks_require_once(): void
    {
        $result = $this->parseRouteFile("<?php\nrequire_once __DIR__ . '/other-routes.php';");

        $this->assertFalse($result['valid']);
    }

    public function test_blocks_include(): void
    {
        $result = $this->parseRouteFile("<?php\ninclude 'other-routes.php';");

        $this->assertFalse($result['valid']);
    }

    public function test_blocks_include_once(): void
    {
        $result = $this->parseRouteFile("<?php\ninclude_once 'other-routes.php';");

        $this->assertFalse($result['valid']);
    }

    // =========================================================================
    // COMMENTS SHOULD NOT TRIGGER FALSE POSITIVES
    // =========================================================================

    public function test_ignores_router_patterns_in_comments(): void
    {
        $result = $this->parseRouteFile("<?php
// Don't use app('router') here
/* Route::any is not allowed */
Route::get('/valid', fn() => 'ok');
");

        $this->assertTrue($result['valid']);
        $this->assertContains('/valid', $result['routes']);
    }

    public function test_ignores_router_patterns_in_multiline_comments(): void
    {
        $result = $this->parseRouteFile("<?php
/**
 * This route file demonstrates valid routes.
 * Note: app('router') and Route::any() are blocked.
 */
Route::get('/valid', fn() => 'ok');
");

        $this->assertTrue($result['valid']);
    }

    // =========================================================================
    // SRC FILE SCANNING TESTS
    // =========================================================================

    public function test_src_scan_blocks_route_in_helper_class(): void
    {
        $errors = $this->scanSrcForRouterUsage("<?php
namespace MyPlugin;

class Helper {
    public function registerRoutes() {
        Route::get('/bypass', fn() => 'bad');
    }
}
");

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Route', $errors[0]);
    }

    public function test_src_scan_blocks_lowercase_route(): void
    {
        $errors = $this->scanSrcForRouterUsage("<?php
namespace MyPlugin;

class Helper {
    public function registerRoutes() {
        route::get('/bypass', fn() => 'bad');
    }
}
");

        $this->assertNotEmpty($errors);
    }

    public function test_src_scan_blocks_app_router(): void
    {
        $errors = $this->scanSrcForRouterUsage("<?php
namespace MyPlugin;

class Helper {
    public function registerRoutes() {
        \$router = app('router');
        \$router->get('/bypass', fn() => 'bad');
    }
}
");

        $this->assertNotEmpty($errors);
    }

    public function test_src_scan_blocks_app_router_uppercase(): void
    {
        $errors = $this->scanSrcForRouterUsage("<?php
namespace MyPlugin;

class Helper {
    public function registerRoutes() {
        \$router = APP('router');
    }
}
");

        $this->assertNotEmpty($errors);
    }

    public function test_src_scan_blocks_aliased_route_lowercase_usage(): void
    {
        $errors = $this->scanSrcForRouterUsage("<?php
namespace MyPlugin;

use Illuminate\\Support\\Facades\\Route as R;

class Helper {
    public function registerRoutes() {
        r::get('/bypass', fn() => 'bad');
    }
}
");

        $this->assertNotEmpty($errors);
    }

    public function test_src_scan_allows_legitimate_code(): void
    {
        $errors = $this->scanSrcForRouterUsage("<?php
namespace MyPlugin;

class Helper {
    public function doSomething() {
        return 'hello world';
    }

    public function getConfig() {
        return config('myPlugin.setting');
    }
}
");

        $this->assertEmpty($errors);
    }

    public function test_src_scan_allows_route_in_comments(): void
    {
        $errors = $this->scanSrcForRouterUsage("<?php
namespace MyPlugin;

/**
 * This class does NOT register routes.
 * Route registration is done in routes/public.php
 * Don't use Route::get() or app('router') here.
 */
class Helper {
    public function doSomething() {
        return 'ok';
    }
}
");

        $this->assertEmpty($errors);
    }

    // =========================================================================
    // EDGE CASES
    // =========================================================================

    public function test_detects_unparseable_route_calls(): void
    {
        // If there are Route:: calls that don't match our parsing pattern,
        // they should be flagged
        $result = $this->parseRouteFile("<?php
Route::get('/valid', fn() => 'ok');
Route::someCustomMethod('/unknown');
");

        // This should fail because someCustomMethod can't be parsed
        $this->assertFalse($result['valid']);
    }

    public function test_empty_route_file_is_valid(): void
    {
        $result = $this->parseRouteFile("<?php\n// No routes defined\n");

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['routes']);
    }
}
