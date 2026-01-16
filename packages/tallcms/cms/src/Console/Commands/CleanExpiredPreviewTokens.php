<?php

namespace TallCms\Cms\Console\Commands;

use TallCms\Cms\Models\CmsPreviewToken;
use Illuminate\Console\Command;

class CleanExpiredPreviewTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cms:clean-preview-tokens
                            {--dry-run : Show what would be deleted without actually deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete expired or over-limit preview tokens';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        // Find tokens that are expired or over max views
        $query = CmsPreviewToken::where(function ($q) {
            $q->where('expires_at', '<', now())
                ->orWhere(function ($q2) {
                    $q2->whereNotNull('max_views')
                        ->whereColumn('view_count', '>=', 'max_views');
                });
        });

        $count = $query->count();

        if ($count === 0) {
            $this->info('No expired preview tokens found.');

            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->warn("Would delete {$count} expired/over-limit preview token(s).");

            // Show details
            $tokens = $query->get();
            $this->table(
                ['ID', 'Content Type', 'Content ID', 'Expires At', 'Views', 'Max Views', 'Status'],
                $tokens->map(fn ($token) => [
                    $token->id,
                    class_basename($token->tokenable_type),
                    $token->tokenable_id,
                    $token->expires_at->toDateTimeString(),
                    $token->view_count,
                    $token->max_views ?? 'Unlimited',
                    $token->isExpired() ? 'Expired' : 'Over limit',
                ])
            );

            return self::SUCCESS;
        }

        // Delete the tokens
        $deleted = $query->delete();

        $this->info("Deleted {$deleted} expired/over-limit preview token(s).");

        return self::SUCCESS;
    }
}
