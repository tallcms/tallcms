<?php

declare(strict_types=1);

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
        $prefix = config('tallcms.database.prefix', 'tallcms_');

        Schema::create($prefix.'webhook_deliveries', function (Blueprint $table) use ($prefix) {
            $table->id();
            $table->string('delivery_id', 64)->unique();
            $table->foreignId('webhook_id')->constrained($prefix.'webhooks')->cascadeOnDelete();
            $table->string('event');
            $table->json('payload');
            $table->unsignedTinyInteger('attempt');
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->text('response_body')->nullable();
            $table->json('response_headers')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->boolean('success');
            $table->timestamp('next_retry_at')->nullable();
            $table->timestamps();

            $table->index(['webhook_id', 'created_at']);
            $table->index(['success', 'next_retry_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $prefix = config('tallcms.database.prefix', 'tallcms_');
        Schema::dropIfExists($prefix.'webhook_deliveries');
    }
};
