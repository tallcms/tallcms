<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Add multilingual support to TallCMS content tables.
 *
 * This migration converts string columns to JSON format for spatie/laravel-translatable.
 * Existing data is preserved by wrapping it in the default locale structure: {"en": "value"}
 *
 * Important:
 * - Unique slug constraints are replaced with application-level validation
 * - Migration is reversible but translations will be lost on rollback
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get the default locale from config
        $defaultLocale = config('tallcms.i18n.default_locale', 'en');
        $driver = DB::getDriverName();

        // Drop unique constraints FIRST (before dropping columns)
        // Uniqueness is now enforced via UniqueTranslatableSlug validation rule
        $this->dropUniqueConstraints();

        // Also drop indexes on slug columns (required for SQLite)
        $this->dropSlugIndexes();

        // 1. Convert tallcms_pages columns
        $this->convertTable('tallcms_pages', [
            'title' => 'string',
            'slug' => 'string',
            'meta_title' => 'string',
            'meta_description' => 'text',
        ], $defaultLocale, $driver);

        // 1b. Convert tallcms_pages content column (special handling for JSON content)
        $this->convertJsonColumn('tallcms_pages', 'content', $defaultLocale);

        // 2. Convert tallcms_posts columns
        $this->convertTable('tallcms_posts', [
            'title' => 'string',
            'slug' => 'string',
            'excerpt' => 'text',
            'meta_title' => 'string',
            'meta_description' => 'text',
        ], $defaultLocale, $driver);

        // 2b. Convert tallcms_posts content column (special handling for JSON content)
        $this->convertJsonColumn('tallcms_posts', 'content', $defaultLocale);

        // 3. Convert tallcms_categories columns
        $this->convertTable('tallcms_categories', [
            'name' => 'string',
            'slug' => 'string',
            'description' => 'text',
        ], $defaultLocale, $driver);

        // 4. Convert tallcms_menu_items label column
        $this->convertTable('tallcms_menu_items', [
            'label' => 'string',
        ], $defaultLocale, $driver);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $defaultLocale = config('tallcms.i18n.default_locale', 'en');
        $driver = DB::getDriverName();

        // Restore unique constraints first
        $this->restoreUniqueConstraints();

        // Reverse conversions (extract default locale value from JSON)
        $this->revertTable('tallcms_pages', [
            'title' => 'string',
            'slug' => 'string',
            'meta_title' => 'string',
            'meta_description' => 'text',
        ], $defaultLocale, $driver);

        // Revert content column for pages
        $this->revertJsonColumn('tallcms_pages', 'content', $defaultLocale);

        $this->revertTable('tallcms_posts', [
            'title' => 'string',
            'slug' => 'string',
            'excerpt' => 'text',
            'meta_title' => 'string',
            'meta_description' => 'text',
        ], $defaultLocale, $driver);

        // Revert content column for posts
        $this->revertJsonColumn('tallcms_posts', 'content', $defaultLocale);

        $this->revertTable('tallcms_categories', [
            'name' => 'string',
            'slug' => 'string',
            'description' => 'text',
        ], $defaultLocale, $driver);

        $this->revertTable('tallcms_menu_items', [
            'label' => 'string',
        ], $defaultLocale, $driver);
    }

    /**
     * Convert table columns to JSON format and migrate existing data.
     */
    protected function convertTable(string $table, array $columns, string $defaultLocale, string $driver): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        foreach ($columns as $column => $type) {
            if (! Schema::hasColumn($table, $column)) {
                continue;
            }

            // Step 1: Add temporary column for JSON data
            $tempColumn = $column . '_json';

            Schema::table($table, function (Blueprint $blueprint) use ($tempColumn) {
                $blueprint->json($tempColumn)->nullable();
            });

            // Step 2: Migrate data - wrap existing values in locale structure
            DB::table($table)->orderBy('id')->chunk(100, function ($rows) use ($table, $column, $tempColumn, $defaultLocale) {
                foreach ($rows as $row) {
                    $value = $row->{$column};

                    // Handle null values
                    if ($value === null) {
                        DB::table($table)
                            ->where('id', $row->id)
                            ->update([$tempColumn => null]);

                        continue;
                    }

                    // Wrap value in locale structure
                    $jsonValue = json_encode([$defaultLocale => $value]);

                    DB::table($table)
                        ->where('id', $row->id)
                        ->update([$tempColumn => $jsonValue]);
                }
            });

            // Step 3: Drop old column and rename temp column
            Schema::table($table, function (Blueprint $blueprint) use ($column) {
                $blueprint->dropColumn($column);
            });

            Schema::table($table, function (Blueprint $blueprint) use ($column, $tempColumn) {
                $blueprint->renameColumn($tempColumn, $column);
            });
        }
    }

    /**
     * Revert table columns from JSON back to original format.
     */
    protected function revertTable(string $table, array $columns, string $defaultLocale, string $driver): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        foreach ($columns as $column => $type) {
            if (! Schema::hasColumn($table, $column)) {
                continue;
            }

            // Step 1: Add temporary column for string data
            $tempColumn = $column . '_str';

            Schema::table($table, function (Blueprint $blueprint) use ($tempColumn, $type) {
                if ($type === 'text') {
                    $blueprint->text($tempColumn)->nullable();
                } else {
                    $blueprint->string($tempColumn)->nullable();
                }
            });

            // Step 2: Extract default locale value from JSON
            DB::table($table)->orderBy('id')->chunk(100, function ($rows) use ($table, $column, $tempColumn, $defaultLocale) {
                foreach ($rows as $row) {
                    $jsonValue = $row->{$column};

                    if ($jsonValue === null) {
                        DB::table($table)
                            ->where('id', $row->id)
                            ->update([$tempColumn => null]);

                        continue;
                    }

                    $decoded = json_decode($jsonValue, true);
                    $value = $decoded[$defaultLocale] ?? null;

                    DB::table($table)
                        ->where('id', $row->id)
                        ->update([$tempColumn => $value]);
                }
            });

            // Step 3: Drop JSON column and rename temp column
            Schema::table($table, function (Blueprint $blueprint) use ($column) {
                $blueprint->dropColumn($column);
            });

            Schema::table($table, function (Blueprint $blueprint) use ($column, $tempColumn) {
                $blueprint->renameColumn($tempColumn, $column);
            });
        }
    }

    /**
     * Convert a JSON column to translatable format (wrap existing JSON in locale key).
     *
     * Unlike convertTable(), this handles columns that already contain JSON data
     * by wrapping the entire JSON value inside a locale structure.
     *
     * Before: {"type":"doc","content":[...]}
     * After:  {"en":{"type":"doc","content":[...]}}
     */
    protected function convertJsonColumn(string $table, string $column, string $defaultLocale): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }

        // Update each row: wrap existing JSON in locale structure
        DB::table($table)->orderBy('id')->chunk(100, function ($rows) use ($table, $column, $defaultLocale) {
            foreach ($rows as $row) {
                $value = $row->{$column};

                // Skip null or empty values
                if ($value === null || $value === '') {
                    continue;
                }

                // Check if already in translatable format (has locale key at top level)
                $decoded = json_decode($value, true);
                if ($decoded !== null && isset($decoded[$defaultLocale])) {
                    // Already migrated, skip
                    continue;
                }

                // Wrap the existing JSON content in locale structure
                // We store the decoded content under the locale key, then re-encode
                $wrapped = json_encode([$defaultLocale => $decoded ?? $value]);

                DB::table($table)
                    ->where('id', $row->id)
                    ->update([$column => $wrapped]);
            }
        });
    }

    /**
     * Revert a JSON column from translatable format back to direct JSON.
     *
     * Before: {"en":{"type":"doc","content":[...]}}
     * After:  {"type":"doc","content":[...]}
     */
    protected function revertJsonColumn(string $table, string $column, string $defaultLocale): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }

        DB::table($table)->orderBy('id')->chunk(100, function ($rows) use ($table, $column, $defaultLocale) {
            foreach ($rows as $row) {
                $value = $row->{$column};

                if ($value === null) {
                    continue;
                }

                $decoded = json_decode($value, true);
                if ($decoded === null) {
                    continue;
                }

                // Extract the default locale content
                $content = $decoded[$defaultLocale] ?? $decoded;
                $unwrapped = is_array($content) ? json_encode($content) : $content;

                DB::table($table)
                    ->where('id', $row->id)
                    ->update([$column => $unwrapped]);
            }
        });
    }

    /**
     * Drop unique constraints on slug columns.
     */
    protected function dropUniqueConstraints(): void
    {
        $tables = ['tallcms_pages', 'tallcms_posts', 'tallcms_categories'];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'slug')) {
                try {
                    Schema::table($table, function (Blueprint $blueprint) use ($table) {
                        // Laravel creates index names like tablename_column_unique
                        $indexName = $table . '_slug_unique';
                        $blueprint->dropUnique($indexName);
                    });
                } catch (\Throwable) {
                    // Index might not exist, ignore
                }
            }
        }
    }

    /**
     * Drop regular indexes on slug columns (required for SQLite column drops).
     */
    protected function dropSlugIndexes(): void
    {
        $tables = ['tallcms_pages', 'tallcms_posts', 'tallcms_categories'];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'slug')) {
                try {
                    Schema::table($table, function (Blueprint $blueprint) use ($table) {
                        // Laravel creates index names like tablename_column_index
                        $indexName = $table . '_slug_index';
                        $blueprint->dropIndex($indexName);
                    });
                } catch (\Throwable) {
                    // Index might not exist, ignore
                }
            }
        }
    }

    /**
     * Restore unique constraints on slug columns.
     */
    protected function restoreUniqueConstraints(): void
    {
        $tables = ['tallcms_pages', 'tallcms_posts', 'tallcms_categories'];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'slug')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->unique('slug');
                });
            }
        }
    }
};
