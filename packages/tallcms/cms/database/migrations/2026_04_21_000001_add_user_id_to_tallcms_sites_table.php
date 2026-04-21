<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add user_id to tallcms_sites for existing installs.
 *
 * The core create migration (2026_04_20_000001) was initially released
 * without user_id. This migration adds it for installs that already ran
 * the original version.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tallcms_sites')) {
            return;
        }

        if (Schema::hasColumn('tallcms_sites', 'user_id')) {
            return;
        }

        Schema::table('tallcms_sites', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->after('uuid');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        // Don't drop — multisite plugin depends on this column
    }
};
