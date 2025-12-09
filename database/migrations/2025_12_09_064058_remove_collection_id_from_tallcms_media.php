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
            $table->dropForeign(['collection_id']);
            $table->dropIndex(['collection_id', 'created_at']);
            $table->dropColumn('collection_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tallcms_media', function (Blueprint $table) {
            $table->foreignId('collection_id')->nullable()->constrained('tallcms_media_collections')->onDelete('set null');
            $table->index(['collection_id', 'created_at']);
        });
    }
};
