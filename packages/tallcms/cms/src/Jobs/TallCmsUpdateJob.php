<?php

declare(strict_types=1);

namespace TallCms\Cms\Jobs;

use TallCms\Cms\Services\TallCmsUpdater;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class TallCmsUpdateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 1;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 600; // 10 minutes

    /**
     * Whether to skip database backup (safe for deserialization of old jobs).
     */
    public bool $skipDbBackup = false;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $targetVersion,
        bool $skipDbBackup = false
    ) {
        $this->skipDbBackup = $skipDbBackup;
    }

    /**
     * Execute the job.
     */
    public function handle(TallCmsUpdater $updater): void
    {
        Log::info('TallCmsUpdateJob: Starting update', ['version' => $this->targetVersion]);

        try {
            $updater->updateState([
                'status' => 'in_progress',
                'execution_method' => 'queue',
                'job_id' => $this->job?->getJobId(),
            ]);

            // Run the actual update command
            $options = [
                '--target' => $this->targetVersion,
                '--force' => true,
            ];

            if ($this->skipDbBackup) {
                $options['--skip-db-backup'] = true;
            }

            $exitCode = Artisan::call('tallcms:update', $options);

            if ($exitCode !== 0) {
                $output = Artisan::output();
                Log::error('TallCmsUpdateJob: Update command failed', [
                    'exit_code' => $exitCode,
                    'output' => $output,
                ]);

                $updater->updateState([
                    'status' => 'failed',
                    'error' => "Update command failed with exit code {$exitCode}",
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('TallCmsUpdateJob: Exception during update', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $updater->updateState([
                'status' => 'failed',
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
        Log::error('TallCmsUpdateJob: Job failed', [
            'version' => $this->targetVersion,
            'error' => $exception?->getMessage(),
        ]);

        try {
            $updater = app(TallCmsUpdater::class);
            $updater->updateState([
                'status' => 'failed',
                'error' => $exception?->getMessage() ?? 'Unknown error',
            ]);
            $updater->clearLock();
        } catch (\Throwable $e) {
            Log::error('TallCmsUpdateJob: Failed to update state on failure', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
