<?php

namespace TallCms\Cms\Filament\Pages;

use TallCms\Cms\Services\TallCmsUpdater;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use Livewire\Attributes\Computed;

class UpdateManual extends Page
{
    use HasPageShield;

    protected string $view = 'tallcms::filament.pages.update-manual';

    protected static ?string $navigationLabel = 'Manual Update';

    protected static ?string $title = 'Manual Update Required';

    protected static bool $shouldRegisterNavigation = false;

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-command-line';
    }

    #[Computed]
    public function updateState(): array
    {
        $updater = app(TallCmsUpdater::class);

        return $updater->getUpdateState();
    }

    #[Computed]
    public function targetVersion(): ?string
    {
        return $this->updateState['version'] ?? null;
    }

    #[Computed]
    public function execAvailable(): bool
    {
        return app(TallCmsUpdater::class)->isExecAvailable();
    }

    #[Computed]
    public function queueAvailable(): bool
    {
        return app(TallCmsUpdater::class)->isQueueAvailable();
    }

    #[Computed]
    public function skipDbBackup(): bool
    {
        return $this->updateState['skip_db_backup'] ?? false;
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

        // Redirect if not in manual mode
        if (($state['status'] ?? '') !== 'pending_manual') {
            $this->redirect(SystemUpdates::getUrl());
        }
    }

    public function cancelUpdate(): void
    {
        $updater = app(TallCmsUpdater::class);
        $updater->clearLock();
        $updater->clearState();

        $this->redirect(SystemUpdates::getUrl());
    }

    public function checkProgress(): void
    {
        $state = $this->updateState;

        if (in_array($state['status'] ?? '', ['in_progress', 'completed', 'failed'])) {
            $this->redirect(UpdateProgress::getUrl());
        }
    }
}
