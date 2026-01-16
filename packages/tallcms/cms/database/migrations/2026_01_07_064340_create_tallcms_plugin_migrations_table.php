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
        Schema::create('tallcms_plugin_migrations', function (Blueprint $table) {
            $table->id();
            $table->string('vendor', 64);
            $table->string('slug', 64);
            $table->string('migration');
            $table->integer('batch');
            $table->timestamp('ran_at');

            $table->unique(['vendor', 'slug', 'migration']);
            $table->index(['vendor', 'slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tallcms_plugin_migrations');
    }
};
