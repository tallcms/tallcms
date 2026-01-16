<?php

declare(strict_types=1);

namespace TallCms\Cms\Console\Commands;

use TallCms\Cms\Exceptions\MissingDependencyException;
use Illuminate\Console\Command;

class TallCmsSignRelease extends Command
{
    protected $signature = 'tallcms:sign-release
                            {file : Path to the file to sign (e.g., checksums.json)}';

    protected $description = 'Sign a release file with Ed25519 (for CI use)';

    public function handle(): int
    {
        $privateKeyHex = env('ED25519_PRIVATE_KEY');

        if (empty($privateKeyHex)) {
            $this->error('ED25519_PRIVATE_KEY environment variable not set');
            $this->error('Generate a keypair with: php artisan tallcms:generate-keypair');

            return 1;
        }

        // Verify sodium is available
        if (! function_exists('sodium_crypto_sign_detached')) {
            $this->error('Sodium functions not available. Please run: composer require paragonie/sodium_compat');

            return 1;
        }

        // Validate private key
        try {
            $privateKey = sodium_hex2bin($privateKeyHex);
        } catch (\Throwable $e) {
            $this->error('ED25519_PRIVATE_KEY is not valid hex: '.$e->getMessage());

            return 1;
        }

        if (strlen($privateKey) !== SODIUM_CRYPTO_SIGN_SECRETKEYBYTES) {
            $this->error('ED25519_PRIVATE_KEY has invalid length.');
            $this->error('Expected '.SODIUM_CRYPTO_SIGN_SECRETKEYBYTES * 2 .' hex chars ('.SODIUM_CRYPTO_SIGN_SECRETKEYBYTES.' bytes).');
            $this->error('Got '.strlen($privateKeyHex).' hex chars.');

            return 1;
        }

        $filePath = $this->argument('file');
        if (! file_exists($filePath)) {
            $this->error("File not found: {$filePath}");

            return 1;
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            $this->error("Failed to read file: {$filePath}");

            return 1;
        }

        // Sign the content
        $signature = sodium_crypto_sign_detached($content, $privateKey);
        $signatureBase64 = base64_encode($signature);

        // Write signature file
        $signaturePath = $filePath.'.sig';
        if (file_put_contents($signaturePath, $signatureBase64) === false) {
            $this->error("Failed to write signature file: {$signaturePath}");

            return 1;
        }

        $this->info("Signed: {$signaturePath}");

        // Verify the signature we just created (sanity check)
        $publicKey = sodium_crypto_sign_publickey_from_secretkey($privateKey);
        if (! sodium_crypto_sign_verify_detached($signature, $content, $publicKey)) {
            $this->error('Self-verification failed! This should not happen.');

            return 1;
        }

        $this->info('Signature verified successfully.');

        return 0;
    }
}
