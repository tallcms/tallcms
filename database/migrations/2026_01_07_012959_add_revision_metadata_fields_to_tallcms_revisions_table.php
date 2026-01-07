<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tallcms_revisions', function (Blueprint $table) {
            $table->boolean('is_manual')->default(false)->after('notes');
            $table->string('content_hash', 64)->nullable()->after('is_manual');
        });

        // Backfill existing revisions as automatic
        DB::table('tallcms_revisions')->update(['is_manual' => false]);

        // Backfill content_hash for existing revisions
        $this->backfillContentHashes();
    }

    /**
     * Backfill content hashes for existing revisions.
     *
     * Uses chunkById() to avoid skipping rows when updating the same table being iterated.
     */
    private function backfillContentHashes(): void
    {
        DB::table('tallcms_revisions')
            ->whereNull('content_hash')
            ->orderBy('id')
            ->chunkById(100, function ($revisions) {
                foreach ($revisions as $revision) {
                    $data = [
                        'title' => $revision->title,
                        'excerpt' => $revision->excerpt,
                        'content' => $revision->content,
                        'meta_title' => $revision->meta_title,
                        'meta_description' => $revision->meta_description,
                        'featured_image' => $revision->featured_image,
                    ];
                    ksort($data);
                    $hash = hash('sha256', serialize($data));

                    DB::table('tallcms_revisions')
                        ->where('id', $revision->id)
                        ->update(['content_hash' => $hash]);
                }
            }, 'id');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tallcms_revisions', function (Blueprint $table) {
            $table->dropColumn(['is_manual', 'content_hash']);
        });
    }
};
