<?php

declare(strict_types=1);

namespace TallCms\Cms\Services;

use Illuminate\Support\Facades\File;

class EnvWriter
{
    private string $envPath;

    private array $envData;

    public function __construct()
    {
        $this->envPath = base_path('.env');
        $this->loadEnvData();
    }

    /**
     * Create minimal .env file if it doesn't exist
     */
    public function createMinimalEnv(): bool
    {
        if (File::exists($this->envPath)) {
            return true;
        }

        try {
            // Copy from .env.example if it exists
            $examplePath = base_path('.env.example');
            if (File::exists($examplePath)) {
                File::copy($examplePath, $this->envPath);
            } else {
                // Create basic .env content with temporary key
                $tempKey = 'base64:'.base64_encode(random_bytes(32));
                $content = "APP_NAME=TallCMS\n";
                $content .= "APP_ENV=production\n";
                $content .= "APP_KEY={$tempKey}\n";
                $content .= "APP_DEBUG=false\n";
                $content .= "APP_URL=http://localhost\n\n";
                $content .= "DB_CONNECTION=mysql\n";
                $content .= "INSTALLER_ENABLED=true\n";

                File::put($this->envPath, $content);
            }

            // Reload env data after creation
            $this->loadEnvData();

            return true;

        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Load existing .env data or create empty array
     */
    private function loadEnvData(): void
    {
        $this->envData = [];

        if (File::exists($this->envPath)) {
            $content = File::get($this->envPath);
            $lines = explode("\n", $content);

            foreach ($lines as $line) {
                $line = trim($line);

                // Skip comments and empty lines
                if (empty($line) || str_starts_with($line, '#')) {
                    continue;
                }

                // Parse KEY=VALUE
                if (str_contains($line, '=')) {
                    [$key, $value] = explode('=', $line, 2);
                    $this->envData[trim($key)] = trim($value, '"\'');
                }
            }
        }
    }

    /**
     * Set environment variable value
     */
    public function set(string $key, string $value): self
    {
        $this->envData[$key] = $value;

        return $this;
    }

    /**
     * Remove an environment variable
     */
    public function unset(string $key): self
    {
        unset($this->envData[$key]);

        return $this;
    }

    /**
     * Set multiple environment variables
     * Pass null as value to remove a variable
     */
    public function setMany(array $variables): self
    {
        foreach ($variables as $key => $value) {
            if ($value === null) {
                $this->unset($key);
            } else {
                $this->set($key, $value);
            }
        }

        return $this;
    }

    /**
     * Get environment variable value
     */
    public function get(string $key, string $default = ''): string
    {
        return $this->envData[$key] ?? $default;
    }

    /**
     * Generate application key if not set
     */
    public function generateAppKey(): self
    {
        if (empty($this->get('APP_KEY'))) {
            $this->set('APP_KEY', 'base64:'.base64_encode(random_bytes(32)));
        }

        return $this;
    }

    /**
     * Set database configuration
     */
    public function setDatabaseConfig(array $config): self
    {
        return $this->setMany([
            'DB_CONNECTION' => $config['connection'] ?? 'mysql',
            'DB_HOST' => $config['host'] ?? 'localhost',
            'DB_PORT' => $config['port'] ?? '3306',
            'DB_DATABASE' => $config['database'] ?? '',
            'DB_USERNAME' => $config['username'] ?? '',
            'DB_PASSWORD' => $config['password'] ?? '',
        ]);
    }

    /**
     * Set application configuration
     */
    public function setAppConfig(array $config): self
    {
        return $this->setMany([
            'APP_NAME' => '"'.($config['name'] ?? 'TallCMS').'"',
            'APP_URL' => $config['url'] ?? 'http://localhost',
            'APP_ENV' => $config['environment'] ?? 'production',
            'APP_DEBUG' => $config['debug'] ? 'true' : 'false',
        ]);
    }

    /**
     * Set mail configuration
     */
    public function setMailConfig(array $config): self
    {
        $variables = [
            'MAIL_MAILER' => $config['mailer'] ?? 'smtp',
            'MAIL_FROM_ADDRESS' => '"'.($config['from_address'] ?? 'noreply@example.com').'"',
            'MAIL_FROM_NAME' => '"'.($config['from_name'] ?? 'TallCMS').'"',
        ];

        if ($config['mailer'] === 'smtp') {
            $variables = array_merge($variables, [
                'MAIL_HOST' => $config['host'] ?? '',
                'MAIL_PORT' => $config['port'] ?? '587',
                'MAIL_USERNAME' => $config['username'] ?? '',
                'MAIL_PASSWORD' => $config['password'] ?? '',
                'MAIL_ENCRYPTION' => $config['encryption'] ?? 'tls',
            ]);
        }

        return $this->setMany($variables);
    }

    /**
     * Set S3-compatible storage configuration
     * Works with AWS S3, DigitalOcean Spaces, MinIO, Backblaze B2, Cloudflare R2, Wasabi, etc.
     *
     * Note: Explicitly removes endpoint/path-style when not provided to allow
     * switching between providers (e.g., MinIO -> AWS) without stale config.
     * Pass null values to remove variables from .env file.
     */
    public function setS3Config(array $config): self
    {
        $variables = [];

        // Credentials (optional - supports IAM roles without static keys)
        // Empty string keeps the key but clears the value; null would remove it
        if (array_key_exists('access_key_id', $config)) {
            $variables['AWS_ACCESS_KEY_ID'] = $config['access_key_id'] ?? '';
        }
        if (array_key_exists('secret_access_key', $config)) {
            $variables['AWS_SECRET_ACCESS_KEY'] = $config['secret_access_key'] ?? '';
        }

        // Region
        if (array_key_exists('region', $config)) {
            $variables['AWS_DEFAULT_REGION'] = $config['region'] ?: 'us-east-1';
        }

        // S3 bucket configuration
        if (! empty($config['bucket'])) {
            $variables['AWS_BUCKET'] = $config['bucket'];
            $variables['FILESYSTEM_DISK'] = 's3';
        }

        // Custom endpoint for non-AWS providers (DigitalOcean, MinIO, etc.)
        // Use null to remove from .env when switching to AWS (which doesn't need endpoint)
        if (array_key_exists('endpoint', $config)) {
            $endpoint = $config['endpoint'] ?? null;
            $variables['AWS_ENDPOINT'] = ! empty($endpoint) ? $endpoint : null;
        }

        // Path-style endpoints (required for MinIO and some other providers)
        // Remove entirely when not needed rather than setting to false
        if (array_key_exists('use_path_style', $config)) {
            $variables['AWS_USE_PATH_STYLE_ENDPOINT'] = ! empty($config['use_path_style']) ? 'true' : null;
        }

        // Custom URL for CDN/domain prefixes (e.g., CloudFront)
        // Clear stale values when switching providers unless explicitly provided
        if (array_key_exists('url', $config)) {
            $url = $config['url'] ?? null;
            $variables['AWS_URL'] = ! empty($url) ? $url : null;
        } else {
            // Clear any existing AWS_URL when reconfiguring storage
            $variables['AWS_URL'] = null;
        }

        return $this->setMany($variables);
    }

    /**
     * Clear S3/cloud storage configuration and revert to local storage
     */
    public function clearS3Config(): self
    {
        return $this->setMany([
            'AWS_ACCESS_KEY_ID' => null,
            'AWS_SECRET_ACCESS_KEY' => null,
            'AWS_DEFAULT_REGION' => null,
            'AWS_BUCKET' => null,
            'AWS_ENDPOINT' => null,
            'AWS_URL' => null,
            'AWS_USE_PATH_STYLE_ENDPOINT' => null,
            'FILESYSTEM_DISK' => 'public',
        ]);
    }

    /**
     * @deprecated Use setS3Config() instead
     */
    public function setAwsConfig(array $config): self
    {
        return $this->setS3Config($config);
    }

    /**
     * Set SES mail configuration
     */
    public function setSesMailConfig(array $config): self
    {
        return $this->setMany([
            'MAIL_MAILER' => 'ses',
            'MAIL_FROM_ADDRESS' => '"'.($config['from_address'] ?? 'noreply@example.com').'"',
            'MAIL_FROM_NAME' => '"'.($config['from_name'] ?? 'TallCMS').'"',
        ]);
    }

    /**
     * Disable installer after successful installation
     */
    public function disableInstaller(): self
    {
        return $this->set('INSTALLER_ENABLED', 'false');
    }

    /**
     * Write .env file to disk
     */
    public function save(): bool
    {
        try {
            $content = $this->buildEnvContent();
            File::put($this->envPath, $content);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if .env file exists and is writable
     */
    public function canWriteEnv(): array
    {
        $envExists = File::exists($this->envPath);
        $dirWritable = is_writable(dirname($this->envPath));
        $fileWritable = $envExists ? is_writable($this->envPath) : $dirWritable;

        return [
            'exists' => $envExists,
            'writable' => $fileWritable,
            'directory_writable' => $dirWritable,
            'can_create' => ! $envExists && $dirWritable,
            'can_update' => $envExists && $fileWritable,
        ];
    }

    /**
     * Build .env file content from data
     */
    private function buildEnvContent(): string
    {
        $lines = [];

        // Add header comment
        $lines[] = '# TallCMS Environment Configuration';
        $lines[] = '# Generated by TallCMS Web Installer';
        $lines[] = '';

        // Group variables by section
        $sections = [
            'Application' => ['APP_NAME', 'APP_ENV', 'APP_KEY', 'APP_DEBUG', 'APP_TIMEZONE', 'APP_URL'],
            'Database' => ['DB_CONNECTION', 'DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD'],
            'Filesystem' => ['FILESYSTEM_DISK'],
            'Cache & Sessions' => ['CACHE_STORE', 'SESSION_DRIVER', 'SESSION_LIFETIME'],
            'Queue' => ['QUEUE_CONNECTION'],
            'Mail' => ['MAIL_MAILER', 'MAIL_HOST', 'MAIL_PORT', 'MAIL_USERNAME', 'MAIL_PASSWORD', 'MAIL_ENCRYPTION', 'MAIL_FROM_ADDRESS', 'MAIL_FROM_NAME'],
            'AWS' => ['AWS_ACCESS_KEY_ID', 'AWS_SECRET_ACCESS_KEY', 'AWS_DEFAULT_REGION', 'AWS_BUCKET', 'AWS_ENDPOINT', 'AWS_USE_PATH_STYLE_ENDPOINT', 'AWS_URL'],
            'Installer' => ['INSTALLER_ENABLED'],
        ];

        foreach ($sections as $sectionName => $keys) {
            $sectionHasValues = false;
            $sectionLines = [];

            foreach ($keys as $key) {
                if (isset($this->envData[$key])) {
                    $value = $this->envData[$key];

                    // Quote values that contain spaces or special characters
                    if (str_contains($value, ' ') || str_contains($value, '#') || str_contains($value, '=')) {
                        $value = '"'.str_replace('"', '\"', $value).'"';
                    }

                    $sectionLines[] = "{$key}={$value}";
                    $sectionHasValues = true;
                }
            }

            // Add section if it has values
            if ($sectionHasValues) {
                $lines[] = "# {$sectionName}";
                $lines = array_merge($lines, $sectionLines);
                $lines[] = '';
            }
        }

        // Add any remaining variables
        $usedKeys = collect($sections)->flatten()->toArray();
        $remainingKeys = array_diff(array_keys($this->envData), $usedKeys);

        if (! empty($remainingKeys)) {
            $lines[] = '# Other';
            foreach ($remainingKeys as $key) {
                $value = $this->envData[$key];
                if (str_contains($value, ' ') || str_contains($value, '#') || str_contains($value, '=')) {
                    $value = '"'.str_replace('"', '\"', $value).'"';
                }
                $lines[] = "{$key}={$value}";
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Backup existing .env file
     */
    public function backup(): bool
    {
        if (File::exists($this->envPath)) {
            $backupPath = $this->envPath.'.backup.'.now()->format('Y-m-d_H-i-s');

            return File::copy($this->envPath, $backupPath);
        }

        return true;
    }
}
