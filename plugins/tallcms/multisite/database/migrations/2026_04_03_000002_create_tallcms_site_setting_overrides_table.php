<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tallcms_site_setting_overrides')) {
            return;
        }

        Schema::create('tallcms_site_setting_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained('tallcms_sites')->cascadeOnDelete();
            $table->string('key');
            $table->longText('value')->nullable();
            $table->string('type', 20)->default('text');
            $table->timestamps();

            $table->unique(['site_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tallcms_site_setting_overrides');
    }
};
