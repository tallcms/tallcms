<?php

declare(strict_types=1);

namespace TallCms\Cms\Console\Commands;

use TallCms\Cms\Services\TallCmsUpdater;
use Illuminate\Console\Command;

class TallCmsVersion extends Command
{
    protected $signature = 'tallcms:version
                            {--check : Check for available updates}';

    protected $description = 'Display TallCMS version information';

    public function handle(TallCmsUpdater $updater): int
    {
        $currentVersion = config('tallcms.version');

        $this->newLine();
        $this->components->twoColumnDetail('TallCMS Version', $currentVersion);
        $this->components->twoColumnDetail('Laravel Version', app()->version());
        $this->components->twoColumnDetail('PHP Version', PHP_VERSION);
        $this->newLine();

        if ($this->option('check')) {
            $this->info('Checking for updates...');

            $latest = $updater->checkForUpdates();

            if (! $latest) {
                $this->warn('Could not check for updates. Check your network connection.');

                return 1;
            }

            $this->components->twoColumnDetail('Latest Version', $latest['version']);
            $this->components->twoColumnDetail('Released', $latest['published_at'] ?? 'Unknown');

            if (version_compare($latest['version'], $currentVersion, '>')) {
                $this->newLine();
                $this->components->warn("Update available: {$currentVersion} → {$latest['version']}");
                $this->line('  Run: php artisan tallcms:update');
                $this->line('  Or update via Admin → Settings → System Updates');
            } else {
                $this->newLine();
                $this->components->info('You are running the latest version.');
            }
        }

        return 0;
    }
}
