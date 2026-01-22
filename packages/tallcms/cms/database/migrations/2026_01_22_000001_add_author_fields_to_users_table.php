<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get the configured user model to determine the table name
        $userModelClass = config('tallcms.plugin_mode.user_model', \App\Models\User::class);

        if (! class_exists($userModelClass)) {
            // Fallback to users table if model doesn't exist yet
            $tableName = 'users';
        } else {
            $tableName = (new $userModelClass)->getTable();
        }

        Schema::table($tableName, function (Blueprint $table) {
            // Slug must be nullable to support the concurrent-safe creation pattern
            // where slug is set to null during creating and populated in created event
            if (! Schema::hasColumn($table->getTable(), 'slug')) {
                $table->string('slug')->unique()->nullable()->after('name');
            }

            if (! Schema::hasColumn($table->getTable(), 'bio')) {
                $table->text('bio')->nullable()->after('email');
            }

            if (! Schema::hasColumn($table->getTable(), 'twitter_handle')) {
                $table->string('twitter_handle', 50)->nullable()->after('bio');
            }
        });

        // Best-effort deletion of static robots.txt
        // Don't fail migration on read-only filesystem
        try {
            $robotsPath = public_path('robots.txt');
            if (file_exists($robotsPath) && is_writable($robotsPath)) {
                @unlink($robotsPath);
            }
        } catch (\Exception $e) {
            Log::warning('Could not delete public/robots.txt: '.$e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $userModelClass = config('tallcms.plugin_mode.user_model', \App\Models\User::class);

        if (! class_exists($userModelClass)) {
            $tableName = 'users';
        } else {
            $tableName = (new $userModelClass)->getTable();
        }

        Schema::table($tableName, function (Blueprint $table) {
            $table->dropColumn(['slug', 'bio', 'twitter_handle']);
        });
    }
};
