<?php

namespace TallCms\Cms\Tests\Feature;

use Illuminate\Console\OutputStyle;
use Illuminate\Console\View\Components\Factory;
use ReflectionMethod;
use ReflectionProperty;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;
use TallCms\Cms\Console\Commands\TallCmsInstall;
use TallCms\Cms\Tests\TestCase;

/**
 * Plugin-mode adopters running `tallcms:install` would previously land
 * on a `Illuminate\Foundation\ViteManifestNotFoundException` the moment
 * they opened the admin, because the host app's Vite manifest hadn't
 * been built. This test locks in the proactive warning emitted at the
 * end of the install command so the friction surfaces in the install
 * output instead of as a runtime 500.
 */
class InstallCommandFrontendAssetsCheckTest extends TestCase
{
    public function test_warns_when_vite_manifest_is_missing(): void
    {
        $manifest = public_path('build/manifest.json');
        $this->ensureManifestAbsent($manifest);

        $output = $this->invokeCheck();

        $this->assertStringContainsString('Frontend assets are not built yet', $output);
        $this->assertStringContainsString('npm install && npm run build', $output);
        $this->assertStringContainsString('npm run dev', $output);
    }

    public function test_silent_when_vite_manifest_is_present(): void
    {
        $manifest = public_path('build/manifest.json');
        $this->ensureManifestPresent($manifest);

        try {
            $output = $this->invokeCheck();

            $this->assertStringNotContainsString('Frontend assets are not built yet', $output,
                'When the manifest exists, the install command must stay silent — '
                .'no warning, no spurious npm guidance in the completion output.');
        } finally {
            $this->ensureManifestAbsent($manifest);
        }
    }

    /**
     * Instantiate the command in isolation, wire up an output buffer, and
     * invoke the protected check directly via reflection. Avoids running
     * the rest of the install pipeline (publishes, migrations, theme
     * activation, vendor:publish side effects) which aren't relevant here.
     */
    protected function invokeCheck(): string
    {
        $command = new TallCmsInstall;
        $command->setLaravel($this->app);

        $buffer = new BufferedOutput;
        $outputStyle = new OutputStyle(new StringInput(''), $buffer);
        $command->setOutput($outputStyle);

        // The components factory is normally wired up in Command::run(); we
        // skip that path so we can call the protected check in isolation,
        // which means we wire the factory up by hand.
        $componentsProperty = new ReflectionProperty($command, 'components');
        $componentsProperty->setAccessible(true);
        $componentsProperty->setValue($command, new Factory($outputStyle));

        $method = new ReflectionMethod($command, 'checkFrontendAssets');
        $method->setAccessible(true);
        $method->invoke($command);

        return $buffer->fetch();
    }

    protected function ensureManifestAbsent(string $manifest): void
    {
        if (file_exists($manifest)) {
            @unlink($manifest);
        }
    }

    protected function ensureManifestPresent(string $manifest): void
    {
        if (! is_dir(dirname($manifest))) {
            mkdir(dirname($manifest), 0755, true);
        }
        file_put_contents($manifest, '{}');
    }
}
