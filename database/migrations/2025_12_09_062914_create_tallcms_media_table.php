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
        Schema::create('tallcms_media', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Original filename
            $table->string('file_name'); // Stored filename
            $table->string('mime_type');
            $table->string('path'); // Storage path
            $table->string('disk')->default('public');
            $table->unsignedBigInteger('size'); // File size in bytes
            $table->json('meta')->nullable(); // Width, height, alt text, etc.
            $table->string('collection_name')->nullable(); // For organization (avatars, gallery, etc.)
            $table->text('alt_text')->nullable();
            $table->text('caption')->nullable();
            $table->timestamps();

            $table->index(['collection_name', 'created_at']);
            $table->index('mime_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tallcms_media');
    }
};
