<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tallcms_preview_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('token', 64)->unique();
            $table->morphs('tokenable'); // tokenable_type, tokenable_id
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('expires_at');
            $table->unsignedInteger('view_count')->default(0);
            $table->unsignedInteger('max_views')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('expires_at', 'idx_expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tallcms_preview_tokens');
    }
};
