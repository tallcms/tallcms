<?php

namespace TallCms\Cms\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class TallCmsSetup extends Command
{
    use Concerns\HasAsciiBanner;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tallcms:setup
                            {--force : Force setup even if already configured}
                            {--no-banner : Suppress the ASCII banner (used when called as sub-command)}
                            {--name= : Admin full name}
                            {--email= : Admin email address}
                            {--password= : Admin password (min 8 chars)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup TallCMS with initial roles, permissions, and admin user';

    /**
     * The guard name for roles/permissions.
     */
    protected string $guardName;

    /**
     * The Filament panel ID.
     */
    protected string $panelId;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (! $this->option('no-banner')) {
            $this->displayHeader();
        }
        $this->info('Setting up TallCMS...');
        $this->newLine();

        // Initialize configuration
        $this->guardName = config('tallcms.auth.guard', 'web');
        $this->panelId = $this->detectPanelId();

        // Check if already setup
        if (! $this->option('force') && $this->isAlreadySetup()) {
            $this->warn('TallCMS appears to be already set up.');
            $this->info('Use --force flag to force re-setup.');

            return Command::FAILURE;
        }

        // Create roles and permissions
        $this->createRolesAndPermissions();

        // Create or update first admin user
        $this->createAdminUser();

        $this->newLine();
        $this->info('TallCMS setup completed successfully!');

        $panelPath = $this->detectPanelPath();
        $this->info("You can now access the admin panel at {$panelPath}");

        return Command::SUCCESS;
    }

    /**
     * Detect the Filament panel ID from configuration or panel providers.
     */
    protected function detectPanelId(): string
    {
        // First check tallcms config
        $configPanelId = config('tallcms.filament.panel_id');
        if ($configPanelId) {
            return $configPanelId;
        }

        // Try to detect from Filament panels
        try {
            $panels = \Filament\Facades\Filament::getPanels();
            if (! empty($panels)) {
                return array_key_first($panels);
            }
        } catch (\Throwable) {
            // Filament not fully booted
        }

        // Try to detect from panel provider files
        $providerPath = app_path('Providers/Filament');
        if (is_dir($providerPath)) {
            $files = glob($providerPath.'/*Provider.php');
            foreach ($files as $file) {
                $content = file_get_contents($file);
                // Look for ->id('something') in the provider
                if (preg_match('/->id\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)/', $content, $matches)) {
                    return $matches[1];
                }
            }
        }

        return 'admin';
    }

    /**
     * Detect the Filament panel path.
     */
    protected function detectPanelPath(): string
    {
        // First check tallcms config
        $configPanelPath = config('tallcms.filament.panel_path');
        if ($configPanelPath) {
            return '/'.ltrim($configPanelPath, '/');
        }

        // Try to get from Filament panels
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
            // Filament not fully booted
        }

        // Try to detect from panel provider files
        $providerPath = app_path('Providers/Filament');
        if (is_dir($providerPath)) {
            $files = glob($providerPath.'/*Provider.php');
            foreach ($files as $file) {
                $content = file_get_contents($file);
                if (preg_match('/->path\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)/', $content, $matches)) {
                    return '/'.ltrim($matches[1], '/');
                }
            }
        }

        return '/admin';
    }

    protected function isAlreadySetup(): bool
    {
        try {
            $userModel = $this->getUserModel();

            return Role::where('name', 'super_admin')
                ->where('guard_name', $this->guardName)
                ->exists() &&
                   $userModel::role('super_admin')->exists();
        } catch (\Exception) {
            // Tables don't exist yet, so setup is not complete
            return false;
        }
    }

    protected function createRolesAndPermissions(): void
    {
        $this->info('Creating roles and permissions...');

        // Create roles with proper guard
        $roles = [
            'super_admin' => 'Super Administrator - Complete system access',
            'administrator' => 'Administrator - Full content and limited user management',
            'editor' => 'Editor - Full content management',
            'author' => 'Author - Create and edit own content',
        ];

        foreach (array_keys($roles) as $roleName) {
            Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => $this->guardName,
            ]);
            $this->line("  Created role: {$roleName}");
        }

        // Generate Shield permissions
        $this->info('Generating Shield permissions...');
        $this->call('shield:generate', [
            '--all' => true,
            '--panel' => $this->panelId,
            '--option' => 'policies_and_permissions',
        ]);

        // Create custom CMS permissions not covered by Shield
        $this->createCustomCmsPermissions();

        // Run ShieldSeeder if it exists (standalone mode)
        if (class_exists('Database\\Seeders\\ShieldSeeder')) {
            $this->info('Running Shield seeder for permissions...');
            $this->call('db:seed', ['--class' => 'ShieldSeeder']);
        }

        // Assign all permissions to super_admin role
        $this->assignSuperAdminPermissions();

        // Now customize the role permissions for our CMS roles
        $this->customizeRolePermissions();

        $this->info('Roles and permissions created!');
        $this->newLine();
    }

    protected function createAdminUser(): void
    {
        $this->info('Setting up admin user...');

        $userModel = $this->getUserModel();

        // Check if we have an existing user
        $existingUser = $userModel::first();

        if ($existingUser && ! $this->option('force')) {
            $makeAdmin = $this->confirm("Found existing user ({$existingUser->email}). Make them super admin?", true);

            if ($makeAdmin) {
                $existingUser->assignRole('super_admin');
                $this->info("{$existingUser->email} is now a super admin!");

                return;
            }
        }

        // Get admin name
        $name = $this->option('name');
        if ($this->option('no-interaction')) {
            if (! $name) {
                throw new \RuntimeException('Admin name is required when using --no-interaction. Use --name="Your Name"');
            }
        } else {
            $name = $name ?: $this->ask('Admin full name', 'Admin User');
        }

        $email = $this->option('email');

        // Validate email in non-interactive mode
        if ($this->option('no-interaction')) {
            if (! $email) {
                throw new \RuntimeException('Admin email is required when using --no-interaction. Use --email="your@email.com"');
            }
            if (! $this->isValidEmail($email)) {
                throw new \RuntimeException("Invalid email format: {$email}. Please provide a valid email address.");
            }
            if ($userModel::where('email', $email)->exists()) {
                throw new \RuntimeException("Email already exists: {$email}. Please use a different email address.");
            }
        } else {
            // Interactive mode - ask for email if not provided
            if ($email === null) {
                $email = $this->ask('Admin email address');
            }
            while (! $email || ! $this->isValidEmail($email) || $userModel::where('email', $email)->exists()) {
                if (! $email) {
                    $this->error('Email address is required.');
                } elseif (! $this->isValidEmail($email)) {
                    $this->error('Please enter a valid email address.');
                } else {
                    $this->error('This email already exists.');
                }
                $email = $this->ask('Admin email address');
            }
        }

        $password = $this->option('password');

        // Validate password in non-interactive mode
        if ($this->option('no-interaction')) {
            if (! $password) {
                throw new \RuntimeException('Admin password is required when using --no-interaction. Use --password="your-password"');
            }
            if (strlen((string) $password) < 8) {
                throw new \RuntimeException('Admin password must be at least 8 characters when using --no-interaction.');
            }
        } else {
            // Interactive mode - ask for password if not provided
            if ($password === null) {
                $password = $this->secret('Admin password');
            }
            while (strlen((string) $password) < 8) {
                $this->error('Password must be at least 8 characters.');
                $password = $this->secret('Admin password');
            }
        }

        // Build user data with only columns that exist
        $userData = [
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
        ];

        // Check for optional columns and add them if they exist
        $tableName = (new $userModel)->getTable();

        if (Schema::hasColumn($tableName, 'is_active')) {
            $userData['is_active'] = true;
        }

        if (Schema::hasColumn($tableName, 'email_verified_at')) {
            $userData['email_verified_at'] = now();
        }

        // Create the user
        $user = $userModel::create($userData);

        // Assign super admin role
        $user->assignRole('super_admin');

        $this->info("Super admin user created: {$email}");
    }

    /**
     * Create custom CMS permissions not covered by Shield's standard CRUD generation.
     * These are workflow-related permissions for content approval and revision viewing.
     */
    protected function createCustomCmsPermissions(): void
    {
        $this->info('Creating custom CMS permissions...');

        // Custom permissions for CMS workflow features
        $customPermissions = [
            // Content approval workflow
            'Approve:CmsPage' => 'Approve pages for publication',
            'Approve:CmsPost' => 'Approve posts for publication',

            // Submit for review (authors)
            'SubmitForReview:CmsPage' => 'Submit pages for review',
            'SubmitForReview:CmsPost' => 'Submit posts for review',

            // Revision history access
            'ViewRevisions:CmsPage' => 'View page revision history',
            'ViewRevisions:CmsPost' => 'View post revision history',

            // Restore revisions
            'RestoreRevisions:CmsPage' => 'Restore page revisions',
            'RestoreRevisions:CmsPost' => 'Restore post revisions',

            // Preview link generation
            'GeneratePreviewLink:CmsPage' => 'Generate shareable preview links for pages',
            'GeneratePreviewLink:CmsPost' => 'Generate shareable preview links for posts',
        ];

        foreach ($customPermissions as $name => $description) {
            Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => $this->guardName,
            ]);
            $this->line("  Created permission: {$name}");
        }
    }

    /**
     * Assign all permissions to super_admin role.
     */
    protected function assignSuperAdminPermissions(): void
    {
        $superAdminRole = Role::where('name', 'super_admin')
            ->where('guard_name', $this->guardName)
            ->first();

        if (! $superAdminRole) {
            return;
        }

        $allPermissions = Permission::where('guard_name', $this->guardName)->get();
        $superAdminRole->syncPermissions($allPermissions);
    }

    protected function customizeRolePermissions(): void
    {
        $allPermissions = Permission::where('guard_name', $this->guardName)->get();

        if ($allPermissions->isEmpty()) {
            $this->error('No permissions found! Shield generation may have failed.');

            return;
        }

        // Get Shield's permission format from config
        $separator = config('filament-shield.permissions.separator', ':');
        $case = config('filament-shield.permissions.case', 'pascal');

        // Super Admin has all permissions (assigned in assignSuperAdminPermissions)
        $superAdminRole = Role::where('name', 'super_admin')
            ->where('guard_name', $this->guardName)
            ->first();
        $this->line("  Super Admin: {$superAdminRole->permissions->count()} permissions (full access)");

        // Administrator: Content + limited user management + some settings
        $administratorPermissions = $allPermissions->filter(function ($permission) use ($separator, $case) {
            return $this->isAdministratorPermission($permission->name, $separator, $case);
        });
        $administratorRole = Role::where('name', 'administrator')
            ->where('guard_name', $this->guardName)
            ->first();
        $administratorRole->syncPermissions($administratorPermissions);
        $this->line("  Administrator: {$administratorPermissions->count()} permissions (content + users + settings)");

        // Editor: Full content management, no users/settings
        $editorPermissions = $allPermissions->filter(function ($permission) use ($separator, $case) {
            return $this->isEditorPermission($permission->name, $separator, $case);
        });
        $editorRole = Role::where('name', 'editor')
            ->where('guard_name', $this->guardName)
            ->first();
        $editorRole->syncPermissions($editorPermissions);
        $this->line("  Editor: {$editorPermissions->count()} permissions (content management only)");

        // Author: Own content + basic operations
        $authorPermissions = $allPermissions->filter(function ($permission) use ($separator, $case) {
            return $this->isAuthorPermission($permission->name, $separator, $case);
        });
        $authorRole = Role::where('name', 'author')
            ->where('guard_name', $this->guardName)
            ->first();
        $authorRole->syncPermissions($authorPermissions);
        $this->line("  Author: {$authorPermissions->count()} permissions (basic content creation)");
    }

    /**
     * Check if permission is for content resources (case-insensitive).
     */
    protected function isContentResource(string $permission): bool
    {
        $lower = strtolower($permission);

        return str_contains($lower, 'cmspage') ||
               str_contains($lower, 'cmspost') ||
               str_contains($lower, 'cmscategory') ||
               str_contains($lower, 'tallcmsmenu') ||
               str_contains($lower, 'tallcmsmedia');
    }

    protected function isAdministratorPermission(string $permission, string $separator, string $case): bool
    {
        // Allow all content management (case-insensitive check)
        if ($this->isContentResource($permission)) {
            return true;
        }

        // Allow user management (but exclude Shield roles)
        $lower = strtolower($permission);
        if (str_contains($lower, 'user') &&
            ! str_contains($lower, 'role') &&
            ! str_contains($lower, 'shield')) {
            return true;
        }

        // Allow site settings page
        if (str_contains($lower, 'sitesettings')) {
            return true;
        }

        return false;
    }

    protected function isEditorPermission(string $permission, string $separator, string $case): bool
    {
        // Full content management
        return $this->isContentResource($permission);
    }

    protected function isAuthorPermission(string $permission, string $separator, string $case): bool
    {
        $lower = strtolower($permission);

        // Basic content permissions (view, create, update) for pages and posts
        if (str_contains($lower, 'cmspage') || str_contains($lower, 'cmspost')) {
            // Allow ViewAny, View, Create, Update, SubmitForReview (check for common permission prefixes)
            if (str_contains($lower, 'viewany') ||
                str_contains($lower, 'view_any') ||
                (str_contains($lower, 'view') && ! str_contains($lower, 'any')) ||
                str_contains($lower, 'create') ||
                str_contains($lower, 'update') ||
                str_contains($lower, 'submitforreview')) {
                return true;
            }
            // Exclude Delete, ForceDelete, Restore, Approve for security
        }

        // View categories only (but can't manage them)
        if (str_contains($lower, 'cmscategory') &&
            (str_contains($lower, 'viewany') || str_contains($lower, 'view_any') ||
             (str_contains($lower, 'view') && ! str_contains($lower, 'any')))) {
            return true;
        }

        // Basic media operations
        if (str_contains($lower, 'tallcmsmedia') &&
            (str_contains($lower, 'viewany') ||
             str_contains($lower, 'view_any') ||
             (str_contains($lower, 'view') && ! str_contains($lower, 'any')) ||
             str_contains($lower, 'create') ||
             str_contains($lower, 'update'))) {
            return true;
        }

        // Basic dashboard access
        if ($lower === 'view_dashboard') {
            return true;
        }

        // Menu management (view only)
        if (str_contains($lower, 'tallcmsmenu') &&
            (str_contains($lower, 'viewany') || str_contains($lower, 'view_any') ||
             (str_contains($lower, 'view') && ! str_contains($lower, 'any')))) {
            return true;
        }

        return false;
    }

    protected function isValidEmail(string $email): bool
    {
        return Validator::make(['email' => $email], [
            'email' => 'required|email',
        ])->passes();
    }

    /**
     * Get the User model class from TallCMS config or auth configuration.
     *
     * @return class-string<\Illuminate\Foundation\Auth\User>
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
