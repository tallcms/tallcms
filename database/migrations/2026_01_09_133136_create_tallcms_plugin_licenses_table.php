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
        Schema::create('tallcms_plugin_licenses', function (Blueprint $table) {
            $table->id();
            $table->string('plugin_slug')->unique();
            $table->string('license_key');
            $table->string('license_source')->default('anystack');
            $table->string('status')->default('pending');
            $table->string('domain')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_validated_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('plugin_slug');
            $table->index('status');
            $table->index(['plugin_slug', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tallcms_plugin_licenses');
    }
};
