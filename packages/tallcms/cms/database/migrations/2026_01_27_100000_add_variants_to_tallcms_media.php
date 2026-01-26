<?php

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
        Schema::table('tallcms_media', function (Blueprint $table) {
            $table->timestamp('optimized_at')->nullable()->after('caption');
            $table->boolean('has_variants')->default(false)->after('optimized_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tallcms_media', function (Blueprint $table) {
            $table->dropColumn(['optimized_at', 'has_variants']);
        });
    }
};
