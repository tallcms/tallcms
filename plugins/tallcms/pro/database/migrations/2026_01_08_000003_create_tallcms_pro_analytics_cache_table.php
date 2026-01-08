<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tallcms_pro_analytics_cache', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 50);
            $table->string('metric', 100);
            $table->string('period', 50);
            $table->json('value');
            $table->timestamp('fetched_at')->useCurrent();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['provider', 'metric', 'period']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tallcms_pro_analytics_cache');
    }
};
