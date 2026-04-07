<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $userTable = $this->resolveUserTable();

        $this->addReviewColumns('tallcms_posts', $userTable);
        $this->addReviewColumns('tallcms_pages', $userTable);
    }

    /**
     * Resolve the user table name from the configured user model.
     */
    private function resolveUserTable(): string
    {
        $userModelClass = config('tallcms.plugin_mode.user_model', \App\Models\User::class);

        if (! class_exists($userModelClass)) {
            return 'users';
        }

        return (new $userModelClass)->getTable();
    }

    /**
     * Add review metadata columns to a content table.
     */
    private function addReviewColumns(string $tableName, string $userTable): void
    {
        Schema::table($tableName, function (Blueprint $table) use ($tableName, $userTable) {
            if (! Schema::hasColumn($tableName, 'last_reviewed_at')) {
                $table->timestamp('last_reviewed_at')->nullable()->after('submitted_at');
            }

            if (! Schema::hasColumn($tableName, 'reviewed_by')) {
                $table->foreignId('reviewed_by')->nullable()->after('last_reviewed_at')
                    ->constrained($userTable)->nullOnDelete();
            }

            if (! Schema::hasColumn($tableName, 'expert_reviewer_name')) {
                $table->string('expert_reviewer_name', 255)->nullable()->after('reviewed_by');
            }

            if (! Schema::hasColumn($tableName, 'expert_reviewer_title')) {
                $table->string('expert_reviewer_title', 255)->nullable()->after('expert_reviewer_name');
            }

            if (! Schema::hasColumn($tableName, 'expert_reviewer_url')) {
                $table->string('expert_reviewer_url', 500)->nullable()->after('expert_reviewer_title');
            }

            if (! Schema::hasColumn($tableName, 'sources')) {
                $table->json('sources')->nullable()->after('expert_reviewer_url');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->dropReviewColumns('tallcms_posts');
        $this->dropReviewColumns('tallcms_pages');
    }

    /**
     * Drop review metadata columns from a content table.
     */
    private function dropReviewColumns(string $tableName): void
    {
        $columns = ['last_reviewed_at', 'reviewed_by', 'expert_reviewer_name',
            'expert_reviewer_title', 'expert_reviewer_url', 'sources'];

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            foreach ($columns as $col) {
                if (Schema::hasColumn($tableName, $col)) {
                    Schema::table($tableName, function (Blueprint $table) use ($col) {
                        $table->dropColumn($col);
                    });
                }
            }
        } else {
            Schema::table($tableName, function (Blueprint $table) use ($tableName, $columns) {
                if (Schema::hasColumn($tableName, 'reviewed_by')) {
                    $table->dropForeign(['reviewed_by']);
                }
                $existing = array_filter($columns, fn ($col) => Schema::hasColumn($tableName, $col));
                if (! empty($existing)) {
                    $table->dropColumn($existing);
                }
            });
        }
    }
};
