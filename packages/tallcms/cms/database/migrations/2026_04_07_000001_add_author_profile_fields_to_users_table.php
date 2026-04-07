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
        $userModelClass = config('tallcms.plugin_mode.user_model', \App\Models\User::class);

        if (! class_exists($userModelClass)) {
            $tableName = 'users';
        } else {
            $tableName = (new $userModelClass)->getTable();
        }

        Schema::table($tableName, function (Blueprint $table) {
            if (! Schema::hasColumn($table->getTable(), 'job_title')) {
                $table->string('job_title', 255)->nullable()->after('twitter_handle');
            }

            if (! Schema::hasColumn($table->getTable(), 'company')) {
                $table->string('company', 255)->nullable()->after('job_title');
            }

            if (! Schema::hasColumn($table->getTable(), 'linkedin_url')) {
                $table->string('linkedin_url', 500)->nullable()->after('company');
            }
        });
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
            $columns = [];
            foreach (['job_title', 'company', 'linkedin_url'] as $col) {
                if (Schema::hasColumn($table->getTable(), $col)) {
                    $columns[] = $col;
                }
            }
            if (! empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
