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
    use Concerns\HasAsciiBanner;

    /**
     * Regex pattern to detect TallCmsPlugin registration in panel provider files.
     *
     * Matches:
     * - ->plugin(TallCmsPlugin::make()
     * - ->plugin(\TallCms\Cms\TallCmsPlugin::make()
     * - ->plugins([TallCmsPlugin::make()])
     * - ->plugins([\n    OtherPlugin::make(),\n    TallCmsPlugin::make(),\n])
     */
    public const PLUGIN_REGISTRATION_PATTERN = '/->plugins?\s*\(\s*(\[[\s\S]*?)?(\\\\?TallCms\\\\Cms\\\\)?TallCmsPlugin::make\s*\(/s';

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
        $this->displayHeader();
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

        // Step 6: Activate TallDaisy theme
        $this->configureTheme();

        // Step 7: Publish Filament assets (required for admin panel CSS)
        $this->publishFilamentAssets();

        // Step 8: Run tallcms:setup for roles and permissions
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
                    '    }',
            ];
        }

        // Check 2: Filament panel provider exists (more flexible detection)
        if (! $this->hasFilamentPanel()) {
            $errors[] = [
                'issue' => 'No Filament panel provider found',
                'fix' => "Install and configure Filament first:\n\n".
                    "    composer require filament/filament:\"^4.0\"\n".
                    '    php artisan filament:install --panels',
            ];
        }

        // Check 3: Filament Shield installed
        if (! class_exists(\BezhanSalleh\FilamentShield\FilamentShieldServiceProvider::class)) {
            $errors[] = [
                'issue' => 'Filament Shield is not installed',
                'fix' => 'This should have been installed as a dependency. Try: composer require bezhansalleh/filament-shield',
            ];
        }

        // Check 4: TallCmsPlugin registered in Filament panel
        if (! $this->isTallCmsPluginRegistered()) {
            $errors[] = [
                'issue' => 'TallCmsPlugin is not registered in your Filament panel',
                'fix' => "Add TallCmsPlugin to your panel provider:\n\n".
                    "    use TallCms\\Cms\\TallCmsPlugin;\n\n".
                    "    return \$panel\n".
                    "        ->plugins([\n".
                    "            TallCmsPlugin::make(),\n".
                    '        ]);',
            ];
        }

        if (! empty($errors)) {
            $this->newLine();
            $this->components->error('Prerequisites not met. Please fix the following:');
            $this->newLine();

            foreach ($errors as $index => $error) {
                $this->line('  <fg=red>'.($index + 1).". {$error['issue']}</>");
                $this->newLine();
                $this->line('     <fg=gray>Fix:</>');
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
     * Check if TallCmsPlugin is registered in a Filament panel.
     *
     * Uses runtime detection first (definitive when Filament is booted),
     * then falls back to scanning panel provider files.
     */
    protected function isTallCmsPluginRegistered(): bool
    {
        // Runtime check — definitive when Filament is booted
        try {
            $panels = \Filament\Facades\Filament::getPanels();
            foreach ($panels as $panel) {
                if ($panel->hasPlugin('tallcms')) {
                    return true;
                }
            }
            // If Filament returned panels but none have the plugin, it's not registered
            if (! empty($panels)) {
                return false;
            }
        } catch (\Throwable) {
            // Filament not fully booted, fall through to file-based check
        }

        // File-based fallback — scan panel provider files
        $providerDirs = array_filter([
            app_path('Providers/Filament'),
            app_path('Providers'),
        ], 'is_dir');

        foreach ($providerDirs as $dir) {
            foreach (glob($dir.'/*.php') as $file) {
                $content = file_get_contents($file);
                if (preg_match(self::PLUGIN_REGISTRATION_PATTERN, $content)) {
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
     * Activate the TallDaisy theme for styled frontend out of the box.
     */
    protected function configureTheme(): void
    {
        // Respect explicit theme opt-out
        if (! config('tallcms.plugin_mode.themes_enabled', true)) {
            $this->components->task('Theme activation', fn () => 'skipped (themes disabled)');

            return;
        }

        // Skip if user has already customized their theme
        $configPath = config_path('theme.php');
        if (file_exists($configPath) && ! $this->option('force')) {
            try {
                $config = include $configPath;
                $active = is_array($config) ? ($config['active'] ?? 'default') : 'default';
            } catch (\Throwable) {
                $this->components->warn('Could not read config/theme.php — re-activating TallDaisy.');
                $active = 'default';
            }
            if ($active !== 'default' && $active !== 'talldaisy') {
                $this->components->task('Theme activation', fn () => "keeping '{$active}'");

                return;
            }
        }

        $this->components->task('Activating TallDaisy theme', function () {
            $themeManager = app(\TallCms\Cms\Services\ThemeManager::class);
            $result = $themeManager->setActiveTheme('talldaisy');

            if (! $result) {
                $this->components->warn(
                    'Could not activate TallDaisy theme. Frontend styling may be missing.'
                );
                $this->line('  Try: <fg=yellow>php artisan theme:list</> to see available themes');
                $this->line('  Check: <fg=yellow>public/themes/talldaisy/</> exists');
            }

            return $result;
        });
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

        // Short reminder when --skip-checks was used (plugin validation was skipped)
        if ($this->option('skip-checks')) {
            $this->components->warn('Reminder: Ensure TallCmsPlugin::make() is registered in your panel provider.');
            $this->newLine();
        }

        // Get the panel path dynamically
        $panelPath = $this->getFilamentPanelPath();

        // Next steps
        $this->components->info('Next steps:');
        $this->components->bulletList([
            "Visit <fg=cyan>{$panelPath}</> to access the admin panel",
            'Create your first page in <fg=cyan>CMS > Pages</>',
            'Configure menus in <fg=cyan>CMS > Menus</>',
            'TallDaisy theme is active — customize in <fg=cyan>Appearance > Themes</>',
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
        if ($this->confirm('All done! Would you like to show some love by starring the TallCMS repo?', true) && $this->input->isInteractive()) {
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
