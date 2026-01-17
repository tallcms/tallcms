<?php

declare(strict_types=1);

namespace TallCms\Cms\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * TallCMS Installation Command
 *
 * This command helps install TallCMS in both standalone and plugin modes.
 * For plugin mode, it publishes necessary files and optionally sets up
 * permissions and roles.
 */
class TallCmsInstall extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'tallcms:install
                            {--migrate : Run database migrations}
                            {--with-permissions : Generate Shield permissions}
                            {--with-roles : Seed default TallCMS roles}
                            {--with-assets : Publish frontend assets}
                            {--panel=admin : Filament panel ID for Shield permissions}
                            {--force : Overwrite existing files}';

    /**
     * The console command description.
     */
    protected $description = 'Install TallCMS in your Laravel application';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->components->info('Installing TallCMS...');
        $this->newLine();

        // Step 1: Publish config
        $this->publishConfig();

        // Step 2: Optionally run migrations
        if ($this->option('migrate') || $this->confirm('Run database migrations?', true)) {
            $this->runMigrations();
        }

        // Step 3: Optionally publish assets
        if ($this->option('with-assets') || $this->confirm('Publish frontend assets? (Required for CMS frontend)', false)) {
            $this->publishAssets();
        }

        // Step 4: Optionally set up permissions
        if ($this->option('with-permissions') || $this->confirm('Generate Shield permissions?', false)) {
            $this->generatePermissions();
        }

        // Step 5: Optionally seed roles
        if ($this->option('with-roles') || $this->confirm('Seed default TallCMS roles?', false)) {
            $this->seedRoles();
        }

        // Show completion message and next steps
        $this->showCompletionMessage();

        return Command::SUCCESS;
    }

    /**
     * Publish TallCMS configuration.
     */
    protected function publishConfig(): void
    {
        $this->components->task('Publishing configuration', function () {
            $params = ['--provider' => 'TallCms\Cms\TallCmsServiceProvider', '--tag' => 'tallcms-config'];

            if ($this->option('force')) {
                $params['--force'] = true;
            }

            $this->callSilently('vendor:publish', $params);

            return true;
        });
    }

    /**
     * Run database migrations.
     */
    protected function runMigrations(): void
    {
        $this->components->task('Running migrations', function () {
            $this->callSilently('migrate');

            return true;
        });
    }

    /**
     * Publish frontend assets.
     */
    protected function publishAssets(): void
    {
        $this->components->task('Publishing assets', function () {
            $params = ['--provider' => 'TallCms\Cms\TallCmsServiceProvider', '--tag' => 'tallcms-assets'];

            if ($this->option('force')) {
                $params['--force'] = true;
            }

            $this->callSilently('vendor:publish', $params);

            return true;
        });
    }

    /**
     * Generate Shield permissions for TallCMS resources.
     */
    protected function generatePermissions(): void
    {
        $this->components->task('Generating Shield permissions', function () {
            // Check if Shield is installed
            if (! class_exists(\BezhanSalleh\FilamentShield\FilamentShieldServiceProvider::class)) {
                $this->components->warn('Filament Shield is not installed. Skipping permission generation.');

                return false;
            }

            $this->callSilently('shield:generate', [
                '--all' => true,
                '--panel' => $this->option('panel'),
                '--option' => 'policies_and_permissions',
            ]);

            return true;
        });
    }

    /**
     * Seed default TallCMS roles.
     */
    protected function seedRoles(): void
    {
        $this->components->task('Seeding default roles', function () {
            $seederClass = \TallCms\Cms\Database\Seeders\TallCmsRolesSeeder::class;

            try {
                // Run the seeder directly via db:seed which handles autoloading
                $this->callSilently('db:seed', ['--class' => $seederClass]);

                return true;
            } catch (\Throwable $e) {
                $this->components->warn('Failed to seed roles: '.$e->getMessage());

                return false;
            }
        });
    }

    /**
     * Show completion message with next steps.
     */
    protected function showCompletionMessage(): void
    {
        $this->newLine();
        $this->components->info('TallCMS installed successfully!');
        $this->newLine();

        $this->components->bulletList([
            'Register TallCmsPlugin in your AdminPanelProvider:',
        ]);

        $this->newLine();
        $this->line('    <fg=gray>// app/Providers/Filament/AdminPanelProvider.php</>');
        $this->line('    <fg=yellow>use</> TallCms\Cms\TallCmsPlugin;');
        $this->newLine();
        $this->line('    <fg=yellow>public function</> <fg=blue>panel</>(<fg=cyan>Panel</> <fg=magenta>$panel</>): <fg=cyan>Panel</>');
        $this->line('    {');
        $this->line('        <fg=yellow>return</> <fg=magenta>$panel</>');
        $this->line('            ->plugins([');
        $this->line('                TallCmsPlugin::make(),');
        $this->line('            ]);');
        $this->line('    }');
        $this->newLine();

        // Show configuration tips
        $this->components->bulletList([
            'Configure plugin mode options in <fg=cyan>config/tallcms.php</>:',
        ]);

        $this->newLine();
        $this->line("    <fg=gray>'plugin_mode' => [</>");
        $this->line("        <fg=green>'routes_enabled'</> => <fg=yellow>true</>,   <fg=gray>// Enable CMS frontend routes</>");
        $this->line("        <fg=green>'routes_prefix'</> => <fg=yellow>'cms'</>,   <fg=gray>// Prefix: /cms, /cms/about, etc.</>");
        $this->line("        <fg=green>'themes_enabled'</> => <fg=yellow>false</>,  <fg=gray>// Enable multi-theme system</>");
        $this->line("        <fg=green>'plugins_enabled'</> => <fg=yellow>false</>, <fg=gray>// Enable plugin system</>");
        $this->line('    ],');
        $this->newLine();

        // Show User model tip
        $this->components->bulletList([
            'Ensure your User model uses HasRoles trait:',
        ]);

        $this->newLine();
        $this->line('    <fg=yellow>use</> Spatie\Permission\Traits\HasRoles;');
        $this->newLine();
        $this->line('    <fg=yellow>class</> <fg=blue>User</> <fg=yellow>extends</> Authenticatable');
        $this->line('    {');
        $this->line('        <fg=yellow>use</> HasRoles;');
        $this->line('    }');
        $this->newLine();

        // Show next steps
        $this->components->info('Next steps:');
        $this->components->bulletList([
            'Visit <fg=cyan>/admin</> to access the admin panel',
            'Create your first page in <fg=cyan>CMS > Pages</>',
            'Configure menus in <fg=cyan>CMS > Menus</>',
            'Customize settings in <fg=cyan>Appearance > Site Settings</>',
        ]);
    }
}
