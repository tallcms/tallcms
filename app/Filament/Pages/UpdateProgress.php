<?php

namespace App\Filament\Pages;

use App\Services\TallCmsUpdater;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use Livewire\Attributes\Computed;

class UpdateProgress extends Page
{
    use HasPageShield;

    protected string $view = 'filament.pages.update-progress';

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

    public function mount(): void
    {
        $state = $this->updateState;

        // Redirect if no update in progress
        if ($state['status'] === 'no_update') {
            $this->redirect(route('filament.admin.pages.system-updates'));
        }
    }

    public function clearAndRetry(): void
    {
        $updater = app(TallCmsUpdater::class);
        $updater->clearLock();
        $updater->clearState();

        $this->redirect(route('filament.admin.pages.system-updates'));
    }

    public function backToUpdates(): void
    {
        $this->redirect(route('filament.admin.pages.system-updates'));
    }
}
