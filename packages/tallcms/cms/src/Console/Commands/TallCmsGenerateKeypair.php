<?php

declare(strict_types=1);

namespace TallCms\Cms\Console\Commands;

use TallCms\Cms\Exceptions\MissingDependencyException;
use Illuminate\Console\Command;

class TallCmsGenerateKeypair extends Command
{
    protected $signature = 'tallcms:generate-keypair
                            {--show : Only display the keys, do not save to file}';

    protected $description = 'Generate an Ed25519 keypair for signing TallCMS releases';

    public function handle(): int
    {
        try {
            $this->verifySodiumAvailable();
        } catch (MissingDependencyException $e) {
            $this->error($e->getMessage());

            return 1;
        }

        $this->info('Generating Ed25519 keypair for TallCMS release signing...');
        $this->newLine();

        // Generate the keypair
        $keypair = sodium_crypto_sign_keypair();
        $privateKey = sodium_bin2hex(sodium_crypto_sign_secretkey($keypair));
        $publicKey = sodium_bin2hex(sodium_crypto_sign_publickey($keypair));

        $this->components->twoColumnDetail('Public Key (64 hex chars)', $publicKey);
        $this->components->twoColumnDetail('Private Key (128 hex chars)', substr($privateKey, 0, 32).'...[redacted]');
        $this->newLine();

        if ($this->option('show')) {
            $this->warn('Full private key (keep this SECRET):');
            $this->line($privateKey);
            $this->newLine();

            return 0;
        }

        // Save to file
        $outputPath = storage_path('app/tallcms-keypair.json');

        $data = [
            'public_key' => $publicKey,
            'private_key' => $privateKey,
            'generated_at' => now()->toIso8601String(),
            'warning' => 'KEEP THE PRIVATE KEY SECRET! Store it in GitHub Secrets as ED25519_PRIVATE_KEY.',
        ];

        file_put_contents($outputPath, json_encode($data, JSON_PRETTY_PRINT));
        chmod($outputPath, 0600); // Restrict permissions

        $this->components->info("Keypair saved to: {$outputPath}");
        $this->newLine();

        $this->components->warn('IMPORTANT: Next steps');
        $this->line('  1. Copy the PUBLIC key to config/tallcms.php or set TALLCMS_UPDATE_PUBLIC_KEY env var');
        $this->line('  2. Copy the PRIVATE key to GitHub Secrets as ED25519_PRIVATE_KEY');
        $this->line('  3. DELETE the keypair file after copying the keys');
        $this->line('  4. NEVER commit the private key to version control');
        $this->newLine();

        $this->components->twoColumnDetail('Public key for config', $publicKey);

        return 0;
    }

    private function verifySodiumAvailable(): void
    {
        if (! function_exists('sodium_crypto_sign_keypair')) {
            throw new MissingDependencyException(
                'Sodium functions not available. Please run: composer require paragonie/sodium_compat'
            );
        }

        // Quick self-test
        try {
            $testKeypair = sodium_crypto_sign_keypair();
            sodium_crypto_sign_secretkey($testKeypair);
            sodium_crypto_sign_publickey($testKeypair);
        } catch (\Throwable $e) {
            throw new MissingDependencyException(
                'Sodium functions available but not working correctly: '.$e->getMessage()
            );
        }
    }
}
