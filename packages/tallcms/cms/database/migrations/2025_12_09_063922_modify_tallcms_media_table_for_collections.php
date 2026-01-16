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
        // Add the new foreign key column
        Schema::table('tallcms_media', function (Blueprint $table) {
            $table->foreignId('collection_id')->nullable()->constrained('tallcms_media_collections')->onDelete('set null');
        });

        // Drop the old index first (SQLite requires this before dropping column)
        Schema::table('tallcms_media', function (Blueprint $table) {
            $table->dropIndex('tallcms_media_collection_name_created_at_index');
        });

        // Now drop the old column
        Schema::table('tallcms_media', function (Blueprint $table) {
            $table->dropColumn('collection_name');
        });

        // Add new index
        Schema::table('tallcms_media', function (Blueprint $table) {
            $table->index(['collection_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop new index
        Schema::table('tallcms_media', function (Blueprint $table) {
            $table->dropIndex(['collection_id', 'created_at']);
        });

        // Add back old column
        Schema::table('tallcms_media', function (Blueprint $table) {
            $table->string('collection_name')->nullable();
        });

        // Drop foreign key and column
        Schema::table('tallcms_media', function (Blueprint $table) {
            $table->dropForeign(['collection_id']);
            $table->dropColumn('collection_id');
        });

        // Add back old index
        Schema::table('tallcms_media', function (Blueprint $table) {
            $table->index(['collection_name', 'created_at']);
        });
    }
};
