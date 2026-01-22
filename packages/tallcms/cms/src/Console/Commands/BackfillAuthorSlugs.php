<?php

declare(strict_types=1);

namespace TallCms\Cms\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use TallCms\Cms\Models\CmsPost;

class BackfillAuthorSlugs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tallcms:backfill-author-slugs
                            {--dry-run : Show what would be updated without making changes}
                            {--force : Skip confirmation prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate slugs for existing users who have authored posts';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Get configured user model and connection info
        $userModelClass = config('tallcms.plugin_mode.user_model', \App\Models\User::class);

        if (! class_exists($userModelClass)) {
            $this->error("User model class '{$userModelClass}' does not exist.");

            return self::FAILURE;
        }

        $userModel = new $userModelClass;
        $usersTable = $userModel->getTable();
        $userKey = $userModel->getKeyName();
        $userConnection = $userModel->getConnectionName() ?? config('database.default');

        $postModel = new CmsPost;
        $postsTable = $postModel->getTable();
        $postConnection = $postModel->getConnectionName() ?? config('database.default');

        // Validate same connection - cross-database whereExists won't work
        if ($userConnection !== $postConnection) {
            $this->error('Users and posts must be on the same database connection.');
            $this->error("Users: {$userConnection}, Posts: {$postConnection}");
            $this->newLine();
            $this->warn('This is a documented requirement. See the upgrade guide for more information.');

            return self::FAILURE;
        }

        // Verify slug column exists
        if (! Schema::connection($userConnection)->hasColumn($usersTable, 'slug')) {
            $this->error("The 'slug' column does not exist on the '{$usersTable}' table.");
            $this->warn('Please run migrations first: php artisan migrate');

            return self::FAILURE;
        }

        $db = DB::connection($userConnection);
        $dryRun = $this->option('dry-run');

        // Find users with posts who don't have slugs
        $users = $db->table($usersTable)
            ->whereExists(function ($query) use ($postsTable, $usersTable, $userKey) {
                $query->select(DB::raw(1))
                    ->from($postsTable)
                    ->whereColumn('author_id', "{$usersTable}.{$userKey}");
            })
            ->whereNull('slug')
            ->get();

        if ($users->isEmpty()) {
            $this->info('No users need slug backfill. All authors already have slugs.');

            return self::SUCCESS;
        }

        $this->info("Found {$users->count()} user(s) needing slug backfill.");

        if ($dryRun) {
            $this->warn('DRY RUN - No changes will be made.');
            $this->newLine();
        }

        // Show preview
        $preview = $users->map(function ($user) use ($userKey, $usersTable, $db) {
            $userId = $user->{$userKey};
            $slug = Str::slug($user->name);

            if (empty($slug)) {
                $slug = "user-{$userId}";
            }

            $slug = $this->makeUnique($slug, $usersTable, $db, $userKey, $userId);

            return [
                'id' => $userId,
                'name' => $user->name,
                'slug' => $slug,
            ];
        });

        $this->table(['ID', 'Name', 'Generated Slug'], $preview);

        if ($dryRun) {
            return self::SUCCESS;
        }

        // Confirm unless --force
        if (! $this->option('force') && ! $this->confirm('Do you want to proceed with these slug assignments?')) {
            $this->info('Operation cancelled.');

            return self::SUCCESS;
        }

        // Perform updates
        $updated = 0;
        $errors = 0;

        $this->withProgressBar($users, function ($user) use ($db, $usersTable, $userKey, &$updated, &$errors) {
            try {
                $userId = $user->{$userKey};
                $slug = Str::slug($user->name);

                if (empty($slug)) {
                    $slug = "user-{$userId}";
                }

                $slug = $this->makeUnique($slug, $usersTable, $db, $userKey, $userId);

                $db->table($usersTable)
                    ->where($userKey, $userId)
                    ->update(['slug' => $slug]);

                $updated++;
            } catch (\Exception $e) {
                $errors++;
            }
        });

        $this->newLine(2);

        if ($updated > 0) {
            $this->info("Successfully updated {$updated} user slug(s).");
        }

        if ($errors > 0) {
            $this->error("Failed to update {$errors} user(s). Check logs for details.");

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Make a slug unique by appending a suffix if needed.
     */
    protected function makeUnique(string $slug, string $table, $db, string $keyName, mixed $excludeId): string
    {
        $originalSlug = $slug;
        $counter = 1;

        while ($db->table($table)->where('slug', $slug)->where($keyName, '!=', $excludeId)->exists()) {
            $counter++;
            $slug = "{$originalSlug}-{$counter}";
        }

        return $slug;
    }
}
