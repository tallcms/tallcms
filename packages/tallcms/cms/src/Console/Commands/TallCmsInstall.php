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
        $this->components->info('🚀 Installing TallCMS...');
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

        // Step 5: Run tallcms:setup for roles and permissions
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
            return Schema::hasTable('tallcms_pages') &&
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

        // Check 2: TallCmsPlugin registered (check if we can detect it)
        // This is harder to check, so we'll just remind them in the completion message

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
                '--provider' => 'Spatie\Permission\PermissionServiceProvider',
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
                '--provider' => 'TallCms\Cms\TallCmsServiceProvider',
                '--tag' => 'tallcms-config',
            ]);

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
        $this->components->info('✅ TallCMS installed successfully!');
        $this->newLine();

        // Reminder to register plugin
        $this->components->warn('Important: Make sure TallCmsPlugin is registered in your panel provider:');
        $this->newLine();
        $this->line('    <fg=yellow>use</> TallCms\Cms\TallCmsPlugin;');
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
    }

    /**
     * Get the Filament panel path.
     */
    protected function getFilamentPanelPath(): string
    {
        try {
            // Try to get the default panel's path from Filament
            $panels = \Filament\Facades\Filament::getPanels();

            if (! empty($panels)) {
                $panel = reset($panels);
                $path = $panel->getPath();

                if ($path) {
                    return '/'.$path;
                }
            }
        } catch (\Throwable) {
            // Filament might not be fully booted during CLI
        }

        // Try to detect from panel provider files
        $providerPath = app_path('Providers/Filament');
        if (is_dir($providerPath)) {
            $files = glob($providerPath.'/*PanelProvider.php');
            foreach ($files as $file) {
                $content = file_get_contents($file);
                // Look for ->path('something') in the provider
                if (preg_match('/->path\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)/', $content, $matches)) {
                    return '/'.$matches[1];
                }
            }
        }

        return 'your admin panel';
    }

    /**
     * Get the User model class from auth configuration.
     */
    protected function getUserModel(): string
    {
        $provider = config('auth.guards.web.provider');

        return config("auth.providers.{$provider}.model", \App\Models\User::class);
    }
}
