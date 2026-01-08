<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tallcms_pro_licenses', function (Blueprint $table) {
            $table->id();
            $table->string('license_key')->unique();
            $table->enum('status', ['active', 'expired', 'invalid', 'pending'])->default('pending');
            $table->string('domain')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_validated_at')->nullable();
            $table->json('validation_response')->nullable();
            $table->timestamps();

            $table->index('license_key');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tallcms_pro_licenses');
    }
};
