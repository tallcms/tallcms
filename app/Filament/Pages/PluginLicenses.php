<?php

namespace App\Filament\Pages;

use App\Services\PluginLicenseService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class PluginLicenses extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationLabel = 'Plugin Licenses';

    protected static ?string $title = 'Plugin Licenses';

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 65;

    protected string $view = 'filament.pages.plugin-licenses';

    public ?string $selected_plugin = null;

    public ?string $license_key = '';

    public array $statuses = [];

    public array $licensablePlugins = [];

    public function mount(): void
    {
        $this->refreshStatuses();
    }

    protected function refreshStatuses(): void
    {
        $licenseService = app(PluginLicenseService::class);
        $this->statuses = $licenseService->getAllStatuses();

        // Build licensable plugins list for dropdown
        $this->licensablePlugins = [];
        foreach ($licenseService->getLicensablePlugins() as $plugin) {
            $this->licensablePlugins[$plugin->getLicenseSlug()] = $plugin->name.' ('.$plugin->version.')';
        }

        // Auto-select first plugin if none selected
        if (empty($this->selected_plugin) && ! empty($this->licensablePlugins)) {
            $this->selected_plugin = array_key_first($this->licensablePlugins);
        }
    }

    protected function getFormSchema(): array
    {
        return [
            Select::make('selected_plugin')
                ->label('Plugin')
                ->options($this->licensablePlugins)
                ->required()
                ->reactive()
                ->afterStateUpdated(fn () => $this->license_key = ''),

            TextInput::make('license_key')
                ->label('License Key')
                ->placeholder('XXXX-XXXX-XXXX-XXXX')
                ->required()
                ->helperText('Enter your license key from your purchase email')
                ->visible(fn () => $this->selected_plugin && ! ($this->statuses[$this->selected_plugin]['has_license'] ?? false)),
        ];
    }

    public function activateLicense(): void
    {
        $data = $this->form->getState();

        if (empty($data['selected_plugin'])) {
            Notification::make()
                ->title('Please select a plugin')
                ->danger()
                ->send();

            return;
        }

        if (empty($data['license_key'])) {
            Notification::make()
                ->title('License key is required')
                ->danger()
                ->send();

            return;
        }

        $result = app(PluginLicenseService::class)->activate(
            $data['selected_plugin'],
            $data['license_key']
        );

        if ($result['valid']) {
            Notification::make()
                ->title('License Activated')
                ->body('The license has been successfully activated!')
                ->success()
                ->send();

            $this->license_key = '';
            $this->refreshStatuses();
        } else {
            // Handle 404 / not supported response
            if ($result['status'] === 'not_supported') {
                Notification::make()
                    ->title('Plugin Not Supported')
                    ->body('This plugin does not support license activation.')
                    ->warning()
                    ->send();
            } else {
                Notification::make()
                    ->title('Activation Failed')
                    ->body($result['message'])
                    ->danger()
                    ->send();
            }
        }
    }

    public function deactivateLicense(string $pluginSlug): void
    {
        $result = app(PluginLicenseService::class)->deactivate($pluginSlug);

        if ($result['success']) {
            Notification::make()
                ->title('License Deactivated')
                ->body('The license has been deactivated from this site.')
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Deactivation Notice')
                ->body($result['message'])
                ->warning()
                ->send();
        }

        $this->refreshStatuses();
    }

    public function refreshLicenseStatus(string $pluginSlug): void
    {
        $licenseService = app(PluginLicenseService::class);

        // Force revalidation by clearing cache and checking validity
        $licenseService->clearCache($pluginSlug);
        $licenseService->isValid($pluginSlug);

        $this->refreshStatuses();

        Notification::make()
            ->title('Status Refreshed')
            ->body('License status has been refreshed from the server.')
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh_all')
                ->label('Refresh All')
                ->icon('heroicon-o-arrow-path')
                ->action(function () {
                    $licenseService = app(PluginLicenseService::class);
                    $licenseService->clearCache();

                    foreach ($this->statuses as $pluginSlug => $status) {
                        if ($status['has_license']) {
                            $licenseService->isValid($pluginSlug);
                        }
                    }

                    $this->refreshStatuses();

                    Notification::make()
                        ->title('All Statuses Refreshed')
                        ->success()
                        ->send();
                })
                ->visible(fn () => collect($this->statuses)->contains('has_license', true)),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        // Only show in navigation if there are licensable plugins
        $licenseService = app(PluginLicenseService::class);

        return $licenseService->getLicensablePlugins()->isNotEmpty();
    }
}
