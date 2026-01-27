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
        $prefix = config('tallcms.database.prefix', 'tallcms_');
        $table = $prefix.'webhook_deliveries';

        Schema::table($table, function (Blueprint $table) {
            // Drop the unique constraint on delivery_id alone
            $table->dropUnique(['delivery_id']);

            // Add composite unique constraint on (delivery_id, attempt)
            $table->unique(['delivery_id', 'attempt']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $prefix = config('tallcms.database.prefix', 'tallcms_');
        $table = $prefix.'webhook_deliveries';

        Schema::table($table, function (Blueprint $table) {
            $table->dropUnique(['delivery_id', 'attempt']);
            $table->unique('delivery_id');
        });
    }
};
