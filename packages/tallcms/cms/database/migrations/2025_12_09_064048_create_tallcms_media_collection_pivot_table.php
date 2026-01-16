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
        Schema::create('tallcms_media_collection_pivot', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_id')->constrained('tallcms_media')->onDelete('cascade');
            $table->foreignId('collection_id')->constrained('tallcms_media_collections')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['media_id', 'collection_id']);
            $table->index(['collection_id', 'media_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tallcms_media_collection_pivot');
    }
};
