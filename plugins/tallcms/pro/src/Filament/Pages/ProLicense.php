<?php

namespace Tallcms\Pro\Filament\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Tallcms\Pro\Services\LicenseService;

class ProLicense extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationLabel = 'Pro License';

    protected static ?string $title = 'TallCMS Pro License';

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 100;

    protected string $view = 'tallcms-pro::filament.pages.pro-license';

    public ?string $license_key = '';

    public array $status = [];

    public function mount(): void
    {
        $this->refreshStatus();
    }

    protected function refreshStatus(): void
    {
        $this->status = app(LicenseService::class)->getStatus();
    }

    protected function getFormSchema(): array
    {
        return [
            TextInput::make('license_key')
                ->label('License Key')
                ->placeholder('XXXX-XXXX-XXXX-XXXX')
                ->required()
                ->helperText('Enter your TallCMS Pro license key from your purchase email'),
        ];
    }

    public function activateLicense(): void
    {
        $data = $this->form->getState();

        if (empty($data['license_key'])) {
            Notification::make()
                ->title('License key is required')
                ->danger()
                ->send();

            return;
        }

        $result = app(LicenseService::class)->activate($data['license_key']);

        if ($result['valid']) {
            Notification::make()
                ->title('License Activated')
                ->body('TallCMS Pro has been successfully activated!')
                ->success()
                ->send();

            $this->license_key = '';
            $this->refreshStatus();
        } else {
            Notification::make()
                ->title('Activation Failed')
                ->body($result['message'])
                ->danger()
                ->send();
        }
    }

    public function deactivateLicense(): void
    {
        $result = app(LicenseService::class)->deactivate();

        if ($result['success']) {
            Notification::make()
                ->title('License Deactivated')
                ->body('Your license has been deactivated from this site.')
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Deactivation Notice')
                ->body($result['message'])
                ->warning()
                ->send();
        }

        $this->refreshStatus();
    }

    public function refreshLicenseStatus(): void
    {
        $license = \Tallcms\Pro\Models\ProLicense::current();

        if ($license) {
            $licenseService = app(LicenseService::class);
            $licenseService->validate($license->license_key);
        }

        $this->refreshStatus();

        Notification::make()
            ->title('Status Refreshed')
            ->body('License status has been refreshed from the server.')
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Refresh Status')
                ->icon('heroicon-o-arrow-path')
                ->action('refreshLicenseStatus')
                ->visible(fn () => $this->status['has_license'] ?? false),
        ];
    }
}
