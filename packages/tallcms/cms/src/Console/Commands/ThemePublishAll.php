<?php

namespace TallCms\Cms\Console\Commands;

use Illuminate\Console\Command;
use TallCms\Cms\Services\ThemeManager;

/**
 * Republish public/themes/<slug> symlinks for every discovered theme.
 *
 * Intended for zero-downtime deploys (Ploi, Forge, Envoyer): each release
 * lands in a fresh directory, so any pre-existing public/themes symlinks
 * are gone — running this in the post-deploy hook restores them without
 * needing to flip the active theme through the admin UI.
 */
class ThemePublishAll extends Command
{
    protected $signature = 'theme:publish-all';

    protected $description = 'Republish public/themes/<slug> symlinks for all discovered themes (deploy hook)';

    public function __construct(
        protected ThemeManager $themeManager
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $themes = $this->themeManager->getAvailableThemes();

        if ($themes->isEmpty()) {
            $this->warn('No themes found.');

            return 0;
        }

        $failed = 0;

        foreach ($themes as $theme) {
            if ($this->themeManager->publishThemeAssets($theme)) {
                $this->line("  ✓ {$theme->slug}");
            } else {
                $this->line("  ✗ {$theme->slug}");
                $failed++;
            }
        }

        $this->newLine();

        if ($failed > 0) {
            $this->error("Republished {$themes->count()} themes ({$failed} failed).");

            return 1;
        }

        $this->info("Republished {$themes->count()} themes.");

        return 0;
    }
}
