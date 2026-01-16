<?php

namespace TallCms\Cms\Console\Commands;

use TallCms\Cms\Services\PluginLicenseService;
use Illuminate\Console\Command;

class LicenseTestCommand extends Command
{
    protected $signature = 'license:test
                            {action=status : Action to perform (status|activate|deactivate|validate|updates)}
                            {--key= : License key to use (defaults to test license)}
                            {--plugin=tallcms/pro : Plugin slug}';

    protected $description = 'Test the license system';

    public function handle(PluginLicenseService $service): int
    {
        $action = $this->argument('action');
        $pluginSlug = $this->option('plugin');
        $licenseKey = $this->option('key') ?? 'TALLCMS-PRO-TEST-LICENSE';

        $this->info("Plugin: {$pluginSlug}");
        $this->info("Environment: " . app()->environment());
        $this->info("Proxy URL: " . config('plugin.license.proxy_url'));
        $this->newLine();

        return match ($action) {
            'status' => $this->showStatus($service, $pluginSlug),
            'activate' => $this->activate($service, $pluginSlug, $licenseKey),
            'deactivate' => $this->deactivate($service, $pluginSlug),
            'validate' => $this->validate($service, $pluginSlug),
            'updates' => $this->checkUpdates($service, $pluginSlug),
            default => $this->error("Unknown action: {$action}") ?? 1,
        };
    }

    protected function showStatus(PluginLicenseService $service, string $pluginSlug): int
    {
        $this->info('=== License Status ===');

        $status = $service->getStatus($pluginSlug);

        $this->table(
            ['Field', 'Value'],
            collect($status)->map(fn ($value, $key) => [
                $key,
                is_bool($value) ? ($value ? 'Yes' : 'No') : (is_array($value) ? json_encode($value) : ($value ?? 'null'))
            ])->toArray()
        );

        return 0;
    }

    protected function activate(PluginLicenseService $service, string $pluginSlug, string $licenseKey): int
    {
        $this->info('=== Activating License ===');
        $this->info("License Key: " . substr($licenseKey, 0, 10) . '...');

        $result = $service->activate($pluginSlug, $licenseKey);

        $this->table(
            ['Field', 'Value'],
            collect($result)->map(fn ($value, $key) => [
                $key,
                is_bool($value) ? ($value ? 'Yes' : 'No') : (is_array($value) ? json_encode($value) : ($value ?? 'null'))
            ])->toArray()
        );

        if ($result['valid'] ?? false) {
            $this->info('License activated successfully!');
        } else {
            $this->error('License activation failed: ' . ($result['message'] ?? 'Unknown error'));
        }

        return ($result['valid'] ?? false) ? 0 : 1;
    }

    protected function deactivate(PluginLicenseService $service, string $pluginSlug): int
    {
        $this->info('=== Deactivating License ===');

        $result = $service->deactivate($pluginSlug);

        $this->table(
            ['Field', 'Value'],
            collect($result)->map(fn ($value, $key) => [
                $key,
                is_bool($value) ? ($value ? 'Yes' : 'No') : ($value ?? 'null')
            ])->toArray()
        );

        if ($result['success'] ?? false) {
            $this->info('License deactivated successfully!');
        } else {
            $this->error('License deactivation failed: ' . ($result['message'] ?? 'Unknown error'));
        }

        return ($result['success'] ?? false) ? 0 : 1;
    }

    protected function validate(PluginLicenseService $service, string $pluginSlug): int
    {
        $this->info('=== Validating License ===');

        // Clear cache to force fresh validation
        $service->clearCache($pluginSlug);

        $isValid = $service->isValid($pluginSlug);

        if ($isValid) {
            $this->info('License is VALID');
        } else {
            $this->error('License is INVALID');
        }

        // Show full status
        $this->newLine();
        return $this->showStatus($service, $pluginSlug);
    }

    protected function checkUpdates(PluginLicenseService $service, string $pluginSlug): int
    {
        $this->info('=== Checking for Updates ===');

        $result = $service->checkForUpdates($pluginSlug);

        $this->table(
            ['Field', 'Value'],
            collect($result)->map(fn ($value, $key) => [
                $key,
                is_bool($value) ? ($value ? 'Yes' : 'No') : (is_array($value) ? json_encode($value) : ($value ?? 'null'))
            ])->toArray()
        );

        if ($result['update_available'] ?? false) {
            $this->info("Update available: v{$result['latest_version']}");
            if ($result['download_url'] ?? null) {
                $this->info("Download: {$result['download_url']}");
            }
        } elseif ($result['license_valid'] ?? false) {
            $this->info('You have the latest version');
        } else {
            $this->warn($result['message'] ?? 'Could not check for updates');
            if ($result['purchase_url'] ?? null) {
                $this->info("Purchase: {$result['purchase_url']}");
            }
        }

        return 0;
    }
}
