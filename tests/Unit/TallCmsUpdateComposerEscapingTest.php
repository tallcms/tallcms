<?php

namespace Tests\Unit;

use TallCms\Cms\Console\Commands\TallCmsUpdate;
use Tests\TestCase;

/**
 * Regression test for the composer-binary-path-with-spaces bug.
 *
 * Herd installs Composer at /Users/<user>/Library/Application Support/Herd/bin/composer
 * — the unescaped space caused tallcms:update to abort during step 14 with:
 *     sh: /Users/dan/Library/Application: No such file or directory
 *
 * resolveComposerCommand() must shell-escape every form it returns so the
 * eventual exec() in TallCmsUpdate::handle() can interpolate it safely.
 */
class TallCmsUpdateComposerEscapingTest extends TestCase
{
    public function test_resolves_path_without_spaces_with_quoting(): void
    {
        $this->assertSame(
            "'/usr/local/bin/composer'",
            $this->invoke('/usr/local/bin/composer'),
        );
    }

    public function test_escapes_space_in_path(): void
    {
        $resolved = $this->invoke('/Users/dan/Library/Application Support/Herd/bin/composer');

        // The shell must be able to interpret the result as a single argument
        $this->assertStringContainsString('Application Support', $resolved);
        $this->assertStringStartsWith("'", $resolved);
        $this->assertStringEndsWith("'", $resolved);
    }

    public function test_phar_path_is_prefixed_with_php_and_both_parts_escaped(): void
    {
        $resolved = $this->invoke('/path with space/composer.phar');

        // PHP_BINARY then escaped phar path
        $this->assertStringContainsString(escapeshellarg(PHP_BINARY), $resolved);
        $this->assertStringContainsString("'/path with space/composer.phar'", $resolved);
    }

    protected function invoke(string $path): string
    {
        $command = new TallCmsUpdate;
        $method = new \ReflectionMethod($command, 'resolveComposerCommand');
        $method->setAccessible(true);

        return $method->invoke($command, $path);
    }
}
