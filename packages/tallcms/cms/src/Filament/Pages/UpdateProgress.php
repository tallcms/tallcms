<?php

namespace TallCms\Cms\Filament\Pages;

use TallCms\Cms\Services\TallCmsUpdater;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use Livewire\Attributes\Computed;

class UpdateProgress extends Page
{
    use HasPageShield;

    protected string $view = 'tallcms::filament.pages.update-progress';

    protected static ?string $navigationLabel = 'Update Progress';

    protected static ?string $title = 'Update Progress';

    protected static bool $shouldRegisterNavigation = false;

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-arrow-path';
    }

    #[Computed]
    public function updateState(): array
    {
        $updater = app(TallCmsUpdater::class);

        return $updater->getUpdateState();
    }

    /**
     * Check if running in standalone mode
     */
    protected static function isStandaloneMode(): bool
    {
        if (config('tallcms.mode') !== null) {
            return config('tallcms.mode') === 'standalone';
        }

        return file_exists(base_path('.tallcms-standalone'));
    }

    public function mount(): void
    {
        // Block access in plugin mode
        if (! static::isStandaloneMode()) {
            abort(404);
        }

        $state = $this->updateState;

        // Redirect if no update in progress
        if ($state['status'] === 'no_update') {
            $this->redirect(SystemUpdates::getUrl());
        }
    }

    public function clearAndRetry(): void
    {
        $updater = app(TallCmsUpdater::class);
        $updater->clearLock();
        $updater->clearState();

        $this->redirect(SystemUpdates::getUrl());
    }

    public function backToUpdates(): void
    {
        $this->redirect(SystemUpdates::getUrl());
    }
}
