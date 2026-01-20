<?php

declare(strict_types=1);

namespace TallCms\Cms\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

/**
 * TallCMS Installation Command
 *
 * This command provides a streamlined installation for TallCMS in plugin mode.
 * It checks prerequisites, publishes migrations, and sets up roles/permissions.
 */
class TallCmsInstall extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'tallcms:install
                            {--skip-checks : Skip prerequisite checks}
                            {--skip-migrations : Skip running migrations}
                            {--skip-setup : Skip roles and permissions setup}
                            {--force : Force installation even if already installed}';

    /**
     * The console command description.
     */
    protected $description = 'Install TallCMS - handles migrations, roles, and permissions';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->newLine();
        $this->components->info('Installing TallCMS...');
        $this->newLine();

        // Check if already installed
        if (! $this->option('force') && $this->isAlreadyInstalled()) {
            $this->components->warn('TallCMS appears to be already installed.');
            $this->line('  Use <fg=yellow>--force</> to reinstall.');
            $this->newLine();

            return Command::SUCCESS;
        }

        // Step 1: Check prerequisites
        if (! $this->option('skip-checks')) {
            if (! $this->checkPrerequisites()) {
                return Command::FAILURE;
            }
        }

        // Step 2: Publish Spatie Permission migrations if needed
        if (! $this->option('skip-migrations')) {
            $this->publishPermissionMigrations();
        }

        // Step 3: Publish TallCMS config
        $this->publishConfig();

        // Step 4: Run migrations
        if (! $this->option('skip-migrations')) {
            $this->runMigrations();
        }

        // Step 5: Publish assets (needed for frontend routes)
        $this->publishAssets();

        // Step 6: Publish Filament assets (required for admin panel CSS)
        $this->publishFilamentAssets();

        // Step 7: Run tallcms:setup for roles and permissions
        if (! $this->option('skip-setup')) {
            $this->runSetup();
        }

        // Show completion message
        $this->showCompletionMessage();

        return Command::SUCCESS;
    }

    /**
     * Check if TallCMS is already installed.
     */
    protected function isAlreadyInstalled(): bool
    {
        try {
            $prefix = config('tallcms.database.prefix', 'tallcms_');

            return Schema::hasTable($prefix.'pages') &&
                   Schema::hasTable('roles') &&
                   \Spatie\Permission\Models\Role::where('name', 'super_admin')->exists();
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Check all prerequisites before installation.
     */
    protected function checkPrerequisites(): bool
    {
        $this->components->task('Checking prerequisites', function () {
            return true;
        });

        $errors = [];

        // Check 1: HasRoles trait on User model
        $userModel = $this->getUserModel();
        $traits = class_uses_recursive($userModel);

        if (! in_array(\Spatie\Permission\Traits\HasRoles::class, $traits)) {
            $errors[] = [
                'issue' => "User model ({$userModel}) is missing the HasRoles trait",
                'fix' => "Add to your User model:\n\n".
                    "    use Spatie\\Permission\\Traits\\HasRoles;\n\n".
                    "    class User extends Authenticatable\n".
                    "    {\n".
                    "        use HasFactory, HasRoles, Notifiable;\n".
                    "    }",
            ];
        }

        // Check 2: Filament panel provider exists (more flexible detection)
        if (! $this->hasFilamentPanel()) {
            $errors[] = [
                'issue' => 'No Filament panel provider found',
                'fix' => "Install and configure Filament first:\n\n".
                    "    composer require filament/filament:\"^4.0\"\n".
                    "    php artisan filament:install --panels",
            ];
        }

        // Check 3: Filament Shield installed
        if (! class_exists(\BezhanSalleh\FilamentShield\FilamentShieldServiceProvider::class)) {
            $errors[] = [
                'issue' => 'Filament Shield is not installed',
                'fix' => 'This should have been installed as a dependency. Try: composer require bezhansalleh/filament-shield',
            ];
        }

        if (! empty($errors)) {
            $this->newLine();
            $this->components->error('Prerequisites not met. Please fix the following:');
            $this->newLine();

            foreach ($errors as $index => $error) {
                $this->line("  <fg=red>".($index + 1).". {$error['issue']}</>");
                $this->newLine();
                $this->line("     <fg=gray>Fix:</>");
                foreach (explode("\n", $error['fix']) as $line) {
                    $this->line("     <fg=green>{$line}</>");
                }
                $this->newLine();
            }

            $this->line('  After fixing these issues, run <fg=yellow>php artisan tallcms:install</> again.');
            $this->newLine();

            return false;
        }

        $this->line('  <fg=green>✓</> All prerequisites met');
        $this->newLine();

        return true;
    }

    /**
     * Check if a Filament panel exists.
     * More flexible than just checking for *PanelProvider.php naming.
     */
    protected function hasFilamentPanel(): bool
    {
        // Check standard location
        $panelProviderPath = app_path('Providers/Filament');
        if (is_dir($panelProviderPath) && ! empty(glob($panelProviderPath.'/*.php'))) {
            return true;
        }

        // Check if Filament can return any panels
        try {
            $panels = \Filament\Facades\Filament::getPanels();
            if (! empty($panels)) {
                return true;
            }
        } catch (\Throwable) {
            // Filament may not be fully booted
        }

        // Check for any registered panel provider in bootstrap/providers.php
        $providersPath = base_path('bootstrap/providers.php');
        if (file_exists($providersPath)) {
            $content = file_get_contents($providersPath);
            if (str_contains($content, 'PanelProvider') || str_contains($content, 'Filament')) {
                return true;
            }
        }

        // Check app/Providers for any Filament panel providers
        $appProvidersPath = app_path('Providers');
        if (is_dir($appProvidersPath)) {
            foreach (glob($appProvidersPath.'/*.php') as $file) {
                $content = file_get_contents($file);
                if (str_contains($content, 'extends PanelProvider') ||
                    str_contains($content, 'Filament\\Panel')) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Publish Spatie Permission migrations if not already published.
     */
    protected function publishPermissionMigrations(): void
    {
        // Check if roles table already exists
        if (Schema::hasTable('roles')) {
            $this->components->task('Spatie Permission migrations', function () {
                return 'already published';
            });

            return;
        }

        $this->components->task('Publishing Spatie Permission migrations', function () {
            $this->callSilently('vendor:publish', [
                '--provider' => 'Spatie\\Permission\\PermissionServiceProvider',
            ]);

            return true;
        });
    }

    /**
     * Publish TallCMS configuration.
     */
    protected function publishConfig(): void
    {
        $this->components->task('Publishing TallCMS configuration', function () {
            $this->callSilently('vendor:publish', [
                '--provider' => 'TallCms\\Cms\\TallCmsServiceProvider',
                '--tag' => 'tallcms-config',
            ]);

            return true;
        });
    }

    /**
     * Publish TallCMS assets (CSS, JS for frontend).
     * Note: Uses --force to ensure latest assets are published.
     */
    protected function publishAssets(): void
    {
        $this->components->task('Publishing TallCMS assets to public/vendor/tallcms/', function () {
            $this->callSilently('vendor:publish', [
                '--provider' => 'TallCms\\Cms\\TallCmsServiceProvider',
                '--tag' => 'tallcms-assets',
                '--force' => true,
            ]);

            return true;
        });

        $this->components->info('Note: This overwrites any customized TallCMS assets in public/vendor/tallcms/');
    }

    /**
     * Publish Filament assets (required for admin panel CSS).
     * This creates symlinks/copies for all Filament package assets including TallCMS admin CSS.
     */
    protected function publishFilamentAssets(): void
    {
        $this->components->task('Publishing Filament assets', function () {
            $this->callSilently('filament:assets');

            return true;
        });
    }

    /**
     * Run database migrations.
     */
    protected function runMigrations(): void
    {
        $this->components->task('Running database migrations', function () {
            $this->callSilently('migrate', ['--force' => true]);

            return true;
        });
    }

    /**
     * Run tallcms:setup for roles and permissions.
     */
    protected function runSetup(): void
    {
        $this->newLine();
        $this->components->info('Setting up roles and permissions...');
        $this->newLine();

        // Call tallcms:setup - it handles everything else
        $this->call('tallcms:setup', ['--force' => $this->option('force')]);
    }

    /**
     * Show completion message with next steps.
     */
    protected function showCompletionMessage(): void
    {
        $this->newLine();
        $this->components->info('TallCMS installed successfully!');
        $this->newLine();

        // Reminder to register plugin
        $this->components->warn('Important: Make sure TallCmsPlugin is registered in your panel provider:');
        $this->newLine();
        $this->line('    <fg=yellow>use</> TallCms\\Cms\\TallCmsPlugin;');
        $this->newLine();
        $this->line('    <fg=yellow>return</> <fg=magenta>$panel</>');
        $this->line('        ->plugin(TallCmsPlugin::make());');
        $this->newLine();

        // Get the panel path dynamically
        $panelPath = $this->getFilamentPanelPath();

        // Next steps
        $this->components->info('Next steps:');
        $this->components->bulletList([
            "Visit <fg=cyan>{$panelPath}</> to access the admin panel",
            'Create your first page in <fg=cyan>CMS > Pages</>',
            'Configure menus in <fg=cyan>CMS > Menus</>',
        ]);
        $this->newLine();

        // Frontend routes info
        $this->components->info('Enable frontend routes (optional):');
        $this->newLine();
        $this->line('    Add to your <fg=cyan>.env</> file:');
        $this->line('       <fg=green>TALLCMS_ROUTES_ENABLED=true</>');
        $this->newLine();
        $this->components->warn('Warning: Without a prefix, this will register the / route and override your app\'s homepage.');
        $this->line('    To avoid this, set: <fg=green>TALLCMS_ROUTES_PREFIX=cms</>');
        $this->newLine();
        $this->line('    <fg=gray>Then mark a CMS page as "Homepage" in the admin panel.</>');
        $this->newLine();

        // Alpine.js requirement
        $this->components->info('Frontend requirements:');
        $this->line('    TallCMS frontend pages require <fg=cyan>Alpine.js</>.');
        $this->line('    Most Laravel apps include it via Livewire. If loading Alpine separately,');
        $this->line('    ensure it loads <fg=yellow>before</> tallcms.js (Alpine components use alpine:init).');
        $this->newLine();

        // Star the repo prompt
        if ($this->confirm('All done! Would you like to show some love by starring the TallCMS repo?', false)) {
            $repoUrl = 'https://github.com/tallcms/tallcms';

            if (PHP_OS_FAMILY === 'Darwin') {
                exec("open {$repoUrl}");
            } elseif (PHP_OS_FAMILY === 'Linux') {
                exec("xdg-open {$repoUrl}");
            } elseif (PHP_OS_FAMILY === 'Windows') {
                exec("start {$repoUrl}");
            }

            $this->components->info('Thank you! Your support means a lot to us.');
        }
    }

    /**
     * Get the Filament panel path.
     */
    protected function getFilamentPanelPath(): string
    {
        // First check tallcms config
        $configPath = config('tallcms.filament.panel_path');
        if ($configPath) {
            return '/'.ltrim($configPath, '/');
        }

        // Try to get the default panel's path from Filament
        try {
            $panels = \Filament\Facades\Filament::getPanels();

            if (! empty($panels)) {
                $panel = reset($panels);
                $path = $panel->getPath();

                if ($path) {
                    return '/'.ltrim($path, '/');
                }
            }
        } catch (\Throwable) {
            // Filament might not be fully booted during CLI
        }

        // Try to detect from panel provider files
        $providerPath = app_path('Providers/Filament');
        if (is_dir($providerPath)) {
            $files = glob($providerPath.'/*.php');
            foreach ($files as $file) {
                $content = file_get_contents($file);
                // Look for ->path('something') in the provider
                if (preg_match('/->path\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)/', $content, $matches)) {
                    return '/'.$matches[1];
                }
            }
        }

        // Check app/Providers for any Filament panel providers
        $appProvidersPath = app_path('Providers');
        if (is_dir($appProvidersPath)) {
            foreach (glob($appProvidersPath.'/*.php') as $file) {
                $content = file_get_contents($file);
                if (str_contains($content, 'extends PanelProvider') ||
                    str_contains($content, 'Filament\\Panel')) {
                    if (preg_match('/->path\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)/', $content, $matches)) {
                        return '/'.$matches[1];
                    }
                }
            }
        }

        return '/admin';
    }

    /**
     * Get the User model class from TallCMS config or auth configuration.
     */
    protected function getUserModel(): string
    {
        // First check TallCMS plugin mode config
        $tallcmsUserModel = config('tallcms.plugin_mode.user_model');
        if ($tallcmsUserModel && class_exists($tallcmsUserModel)) {
            return $tallcmsUserModel;
        }

        // Fall back to auth config using the configured guard
        $guard = config('tallcms.auth.guard', 'web');
        $provider = config("auth.guards.{$guard}.provider");

        return config("auth.providers.{$provider}.model", \App\Models\User::class);
    }
}
