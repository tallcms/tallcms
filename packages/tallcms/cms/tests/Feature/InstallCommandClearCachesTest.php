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
 * Stale Plugin Manager / config cache entries from a prior install can
 * deserialize as __PHP_Incomplete_Class on the first admin page render
 * after a fresh `tallcms:install`, surfacing as a TypeError deep inside
 * Carbon. The install command now runs `optimize:clear` at the end of
 * the pipeline to prevent this. This test pins the user-facing output
 * so the step doesn't silently get removed in a refactor.
 */
class InstallCommandClearCachesTest extends TestCase
{
    public function test_clear_caches_step_emits_task_message(): void
    {
        $output = $this->invokeClearCaches();

        $this->assertStringContainsString('Clearing stale caches', $output,
            'The cache-clearing step must announce itself in the install '
            .'output so users (and CI logs) can see it ran.');
    }

    /**
     * Instantiate the command in isolation and call the protected
     * clearCaches step directly. Mirrors the pattern used by
     * InstallCommandFrontendAssetsCheckTest.
     */
    protected function invokeClearCaches(): string
    {
        $command = new TallCmsInstall;
        $command->setLaravel($this->app);

        $buffer = new BufferedOutput;
        $outputStyle = new OutputStyle(new StringInput(''), $buffer);
        $command->setOutput($outputStyle);

        $componentsProperty = new ReflectionProperty($command, 'components');
        $componentsProperty->setAccessible(true);
        $componentsProperty->setValue($command, new Factory($outputStyle));

        $method = new ReflectionMethod($command, 'clearCaches');
        $method->setAccessible(true);
        $method->invoke($command);

        return $buffer->fetch();
    }
}
