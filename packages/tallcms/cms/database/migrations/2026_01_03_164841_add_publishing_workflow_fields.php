<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Update tallcms_pages
        Schema::table('tallcms_pages', function (Blueprint $table) {
            // Add author_id (posts already have it)
            $table->foreignId('author_id')->nullable()->after('id')->constrained('users')->nullOnDelete();

            // Approval tracking
            $table->foreignId('approved_by')->nullable()->after('published_at')->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->text('rejection_reason')->nullable()->after('approved_at');
            $table->foreignId('submitted_by')->nullable()->after('rejection_reason')->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable()->after('submitted_by');
        });

        // Update tallcms_posts
        Schema::table('tallcms_posts', function (Blueprint $table) {
            // Approval tracking
            $table->foreignId('approved_by')->nullable()->after('published_at')->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->text('rejection_reason')->nullable()->after('approved_at');
            $table->foreignId('submitted_by')->nullable()->after('rejection_reason')->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable()->after('submitted_by');
        });

        // Convert status columns from enum to string (for pending support)
        // MySQL/MariaDB require ALTER to change enum, SQLite stores enums as text already
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement("ALTER TABLE tallcms_pages MODIFY COLUMN status VARCHAR(20) DEFAULT 'draft'");
            DB::statement("ALTER TABLE tallcms_posts MODIFY COLUMN status VARCHAR(20) DEFAULT 'draft'");
        }
        // SQLite: enum columns are already stored as text, no modification needed

        // Map any non-published status to draft
        DB::table('tallcms_pages')
            ->whereNotIn('status', ['published'])
            ->update(['status' => 'draft']);

        DB::table('tallcms_posts')
            ->whereNotIn('status', ['published'])
            ->update(['status' => 'draft']);

        // Backfill author_id on pages with first user (if exists)
        if (Schema::hasTable('users')) {
            $firstUserId = DB::table('users')->first()?->id;
            if ($firstUserId) {
                DB::table('tallcms_pages')
                    ->whereNull('author_id')
                    ->update(['author_id' => $firstUserId]);
            }
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        // SQLite requires separate operations for dropping columns
        if ($driver === 'sqlite') {
            // For SQLite, we need to recreate tables or use separate calls
            // Drop columns one at a time
            Schema::table('tallcms_pages', function (Blueprint $table) {
                $table->dropColumn('author_id');
            });
            Schema::table('tallcms_pages', function (Blueprint $table) {
                $table->dropColumn('approved_by');
            });
            Schema::table('tallcms_pages', function (Blueprint $table) {
                $table->dropColumn('approved_at');
            });
            Schema::table('tallcms_pages', function (Blueprint $table) {
                $table->dropColumn('rejection_reason');
            });
            Schema::table('tallcms_pages', function (Blueprint $table) {
                $table->dropColumn('submitted_by');
            });
            Schema::table('tallcms_pages', function (Blueprint $table) {
                $table->dropColumn('submitted_at');
            });

            Schema::table('tallcms_posts', function (Blueprint $table) {
                $table->dropColumn('approved_by');
            });
            Schema::table('tallcms_posts', function (Blueprint $table) {
                $table->dropColumn('approved_at');
            });
            Schema::table('tallcms_posts', function (Blueprint $table) {
                $table->dropColumn('rejection_reason');
            });
            Schema::table('tallcms_posts', function (Blueprint $table) {
                $table->dropColumn('submitted_by');
            });
            Schema::table('tallcms_posts', function (Blueprint $table) {
                $table->dropColumn('submitted_at');
            });
        } else {
            // MySQL/MariaDB can handle multiple operations in one call
            Schema::table('tallcms_pages', function (Blueprint $table) {
                $table->dropForeign(['author_id']);
                $table->dropColumn('author_id');
                $table->dropForeign(['approved_by']);
                $table->dropColumn(['approved_by', 'approved_at', 'rejection_reason']);
                $table->dropForeign(['submitted_by']);
                $table->dropColumn(['submitted_by', 'submitted_at']);
            });

            Schema::table('tallcms_posts', function (Blueprint $table) {
                $table->dropForeign(['approved_by']);
                $table->dropColumn(['approved_by', 'approved_at', 'rejection_reason']);
                $table->dropForeign(['submitted_by']);
                $table->dropColumn(['submitted_by', 'submitted_at']);
            });

            // Revert to enum (MySQL only)
            DB::statement("ALTER TABLE tallcms_pages MODIFY COLUMN status ENUM('draft', 'published') DEFAULT 'draft'");
            DB::statement("ALTER TABLE tallcms_posts MODIFY COLUMN status ENUM('draft', 'published') DEFAULT 'draft'");
        }
    }
};
