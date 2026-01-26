<?php

declare(strict_types=1);

namespace TallCms\Cms\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use TallCms\Cms\Models\TallcmsMedia;
use TallCms\Cms\Services\ImageOptimizer;

class OptimizeMediaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 120;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 30;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public TallcmsMedia $media
    ) {}

    /**
     * Execute the job.
     */
    public function handle(ImageOptimizer $optimizer): void
    {
        // Skip if optimization is disabled
        if (! config('tallcms.media.optimization.enabled', true)) {
            return;
        }

        // Skip non-images
        if (! $this->media->is_image) {
            return;
        }

        // Skip if already optimized
        if ($this->media->has_variants) {
            return;
        }

        Log::info('OptimizeMediaJob: Starting optimization', [
            'media_id' => $this->media->id,
            'name' => $this->media->name,
        ]);

        try {
            $optimizer->generateVariants($this->media);

            Log::info('OptimizeMediaJob: Optimization complete', [
                'media_id' => $this->media->id,
            ]);
        } catch (\Throwable $e) {
            Log::error('OptimizeMediaJob: Optimization failed', [
                'media_id' => $this->media->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(?\Throwable $exception): void
    {
        Log::error('OptimizeMediaJob: Job failed permanently', [
            'media_id' => $this->media->id,
            'error' => $exception?->getMessage(),
        ]);
    }
}
