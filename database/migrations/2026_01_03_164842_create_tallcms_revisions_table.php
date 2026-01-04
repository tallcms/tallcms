<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tallcms_revisions', function (Blueprint $table) {
            $table->id();
            $table->morphs('revisionable'); // revisionable_type, revisionable_id
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->text('excerpt')->nullable();
            $table->json('content')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('featured_image')->nullable();
            $table->json('additional_data')->nullable();
            $table->unsignedInteger('revision_number');
            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes for efficient querying
            $table->index(['revisionable_type', 'revisionable_id', 'created_at'], 'idx_revisionable_created');
            $table->unique(['revisionable_type', 'revisionable_id', 'revision_number'], 'idx_revision_number_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tallcms_revisions');
    }
};
