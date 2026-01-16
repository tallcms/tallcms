<?php

namespace TallCms\Cms\Console\Commands;

use TallCms\Cms\Services\PluginManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakePluginCommand extends Command
{
    protected $signature = 'make:plugin
                            {name : Plugin name (e.g., "Pro Blocks")}
                            {--vendor= : Vendor name (defaults to "tallcms")}
                            {--description= : Plugin description}
                            {--author= : Plugin author name}
                            {--with-migration : Include example migration}
                            {--with-filament : Include Filament plugin integration}
                            {--with-routes : Include route files}';

    protected $description = 'Create a new plugin scaffold';

    public function handle(PluginManager $manager): int
    {
        $name = $this->argument('name');
        $vendor = $this->option('vendor') ?: 'tallcms';
        $slug = Str::slug($name);
        $description = $this->option('description') ?: "A TallCMS plugin for {$name}";
        $author = $this->option('author') ?: 'TallCMS';

        // Validate vendor and slug
        if (! preg_match('/^[a-z0-9][a-z0-9-]*[a-z0-9]$|^[a-z0-9]$/', $vendor)) {
            $this->error('Vendor must contain only lowercase letters, numbers, and hyphens');

            return self::FAILURE;
        }

        if (! preg_match('/^[a-z0-9][a-z0-9-]*[a-z0-9]$|^[a-z0-9]$/', $slug)) {
            $this->error('Generated slug is invalid. Use a different name.');

            return self::FAILURE;
        }

        // Check if plugin already exists
        if ($manager->isInstalled($vendor, $slug)) {
            $this->error("Plugin {$vendor}/{$slug} already exists.");

            return self::FAILURE;
        }

        $pluginPath = $manager->getPluginPath($vendor, $slug);

        if (File::exists($pluginPath)) {
            $this->error("Directory already exists: {$pluginPath}");

            return self::FAILURE;
        }

        // Create plugin structure
        $this->info("Creating plugin: {$vendor}/{$slug}");

        $namespace = $this->generateNamespace($vendor, $slug);

        // Create directories
        $this->createDirectories($pluginPath);

        // Create plugin.json
        $this->createPluginJson($pluginPath, [
            'name' => $name,
            'slug' => $slug,
            'vendor' => $vendor,
            'namespace' => $namespace,
            'description' => $description,
            'author' => $author,
            'withFilament' => $this->option('with-filament'),
            'withRoutes' => $this->option('with-routes'),
        ]);

        // Create service provider
        $this->createServiceProvider($pluginPath, $namespace, $name);

        // Create Filament plugin if requested
        if ($this->option('with-filament')) {
            $this->createFilamentPlugin($pluginPath, $namespace, $name);
        }

        // Create migration if requested
        if ($this->option('with-migration')) {
            $this->createExampleMigration($pluginPath, $slug);
        }

        // Create route files if requested
        if ($this->option('with-routes')) {
            $this->createRouteFiles($pluginPath);
        }

        // Create README
        $this->createReadme($pluginPath, $name, $description, $vendor, $slug);

        $this->newLine();
        $this->info("Plugin created successfully at: {$pluginPath}");
        $this->newLine();
        $this->line('Next steps:');
        $this->line("  1. Add your plugin code to: {$pluginPath}/src/");

        if ($this->option('with-migration')) {
            $this->line("  2. Update the migration in: {$pluginPath}/database/migrations/");
        }

        $this->line('  3. The plugin will be auto-discovered on the next request');
        $this->line('  4. Or run: php artisan plugin:list');

        return self::SUCCESS;
    }

    protected function generateNamespace(string $vendor, string $slug): string
    {
        $vendorPart = Str::studly($vendor);
        $slugPart = Str::studly($slug);

        return "{$vendorPart}\\{$slugPart}";
    }

    protected function createDirectories(string $pluginPath): void
    {
        $directories = [
            'src',
            'src/Providers',
            'database/migrations',
            'resources/views',
        ];

        foreach ($directories as $dir) {
            File::ensureDirectoryExists("{$pluginPath}/{$dir}", 0755);
        }

        if ($this->option('with-filament')) {
            File::ensureDirectoryExists("{$pluginPath}/src/Filament", 0755);
        }

        if ($this->option('with-routes')) {
            File::ensureDirectoryExists("{$pluginPath}/routes", 0755);
        }
    }

    protected function createPluginJson(string $pluginPath, array $data): void
    {
        $providerClass = "{$data['namespace']}\\Providers\\{$this->getProviderClassName($data['name'])}";

        $pluginJson = [
            'name' => $data['name'],
            'slug' => $data['slug'],
            'vendor' => $data['vendor'],
            'version' => '1.0.0',
            'description' => $data['description'],
            'author' => $data['author'],
            'namespace' => $data['namespace'],
            'provider' => $providerClass,
            'tags' => [],
            'compatibility' => [
                'php' => '^8.2',
                'tallcms' => '^1.0',
            ],
        ];

        if ($data['withFilament']) {
            $pluginJson['filament_plugin'] = "{$data['namespace']}\\Filament\\{$this->getFilamentPluginClassName($data['name'])}";
        }

        if ($data['withRoutes']) {
            $pluginJson['public_routes'] = [];
        }

        File::put(
            "{$pluginPath}/plugin.json",
            json_encode($pluginJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n"
        );
    }

    protected function createServiceProvider(string $pluginPath, string $namespace, string $name): void
    {
        $className = $this->getProviderClassName($name);

        $content = <<<PHP
<?php

namespace {$namespace}\\Providers;

use Illuminate\Support\ServiceProvider;

class {$className} extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Load views
        \$this->loadViewsFrom(__DIR__.'/../../resources/views', '{$this->getViewNamespace($namespace)}');

        // Publish views (optional)
        // \$this->publishes([
        //     __DIR__.'/../../resources/views' => resource_path('views/vendor/{$this->getViewNamespace($namespace)}'),
        // ], 'views');
    }
}

PHP;

        File::put("{$pluginPath}/src/Providers/{$className}.php", $content);
    }

    protected function createFilamentPlugin(string $pluginPath, string $namespace, string $name): void
    {
        $className = $this->getFilamentPluginClassName($name);

        $content = <<<PHP
<?php

namespace {$namespace}\\Filament;

use Filament\\Contracts\\Plugin;
use Filament\\Panel;

class {$className} implements Plugin
{
    public function getId(): string
    {
        return '{$this->getPluginId($namespace)}';
    }

    public function register(Panel \$panel): void
    {
        // Register resources, pages, widgets, etc.
        // \$panel->resources([
        //     // YourResource::class,
        // ]);
    }

    public function boot(Panel \$panel): void
    {
        //
    }

    public static function make(): static
    {
        return app(static::class);
    }
}

PHP;

        File::put("{$pluginPath}/src/Filament/{$className}.php", $content);
    }

    protected function createExampleMigration(string $pluginPath, string $slug): void
    {
        $timestamp = date('Y_m_d_His');
        $tableName = 'tallcms_'.Str::snake(Str::plural($slug));

        $content = <<<PHP
<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('{$tableName}', function (Blueprint \$table) {
            \$table->id();
            \$table->string('name');
            \$table->text('description')->nullable();
            \$table->boolean('is_active')->default(true);
            \$table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('{$tableName}');
    }
};

PHP;

        File::put("{$pluginPath}/database/migrations/{$timestamp}_create_{$tableName}_table.php", $content);
    }

    protected function createRouteFiles(string $pluginPath): void
    {
        // Create web.php (prefixed routes)
        $webRoutes = <<<'PHP'
<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Plugin Web Routes (Prefixed)
|--------------------------------------------------------------------------
|
| These routes are automatically prefixed with /_plugins/{vendor}/{slug}
| and have the 'web' middleware applied.
|
*/

Route::get('/', function () {
    return response()->json(['status' => 'ok']);
});

PHP;

        File::put("{$pluginPath}/routes/web.php", $webRoutes);

        // Create public.php (public routes)
        $publicRoutes = <<<'PHP'
<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Plugin Public Routes
|--------------------------------------------------------------------------
|
| These routes are NOT prefixed and are available at the root level.
| Make sure to declare them in plugin.json under "public_routes".
|
| WARNING: Be careful not to conflict with existing routes.
|
*/

// Example: Route::get('/my-plugin-page', [MyController::class, 'index']);

PHP;

        File::put("{$pluginPath}/routes/public.php", $publicRoutes);
    }

    protected function createReadme(string $pluginPath, string $name, string $description, string $vendor, string $slug): void
    {
        $content = <<<MARKDOWN
# {$name}

{$description}

## Installation

### Via ZIP Upload
1. Download the plugin ZIP file
2. Go to Admin > Settings > Plugins
3. Click "Upload Plugin" and select the ZIP file

### Via File System
1. Extract the plugin to `plugins/{$vendor}/{$slug}/`
2. The plugin will be auto-discovered

## Usage

[Document your plugin usage here]

## License

MIT License

MARKDOWN;

        File::put("{$pluginPath}/README.md", $content);
    }

    protected function getProviderClassName(string $name): string
    {
        return Str::studly($name).'ServiceProvider';
    }

    protected function getFilamentPluginClassName(string $name): string
    {
        return Str::studly($name).'Plugin';
    }

    protected function getViewNamespace(string $namespace): string
    {
        return Str::slug(str_replace('\\', '-', $namespace));
    }

    protected function getPluginId(string $namespace): string
    {
        return Str::slug(str_replace('\\', '-', $namespace));
    }
}
