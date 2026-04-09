<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tallcms_sites', function (Blueprint $table) {
            $table->boolean('domain_verified')->default(false)->after('is_active');
        });

        // Backfill only active sites — inactive sites may be stale
        // and should be reviewed before receiving TLS certificates.
        DB::table('tallcms_sites')
            ->where('is_active', true)
            ->update(['domain_verified' => true]);
    }

    public function down(): void
    {
        Schema::table('tallcms_sites', function (Blueprint $table) {
            $table->dropColumn('domain_verified');
        });
    }
};
