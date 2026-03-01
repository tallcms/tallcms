<?php

namespace TallCms\Cms\Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use TallCms\Cms\Tests\TestCase;

class TallCmsPostInstallCommandTest extends TestCase
{
    private function runAndCaptureOutput(): string
    {
        ob_start();
        $exitCode = Artisan::call('tallcms:post-install');
        $output = ob_get_clean();

        $this->assertSame(0, $exitCode);

        return $output;
    }

    public function test_post_install_command_exits_with_success(): void
    {
        $this->runAndCaptureOutput();
    }

    public function test_post_install_command_displays_success_message(): void
    {
        $output = $this->runAndCaptureOutput();

        $this->assertStringContainsString('TallCMS installed successfully!', $output);
    }

    public function test_post_install_command_displays_next_steps(): void
    {
        $output = $this->runAndCaptureOutput();

        $this->assertStringContainsString('Next steps', $output);
        $this->assertStringContainsString('npm install', $output);
    }

    public function test_post_install_command_displays_install_url(): void
    {
        $output = $this->runAndCaptureOutput();

        $this->assertStringContainsString('localhost:8000/install', $output);
    }

    public function test_post_install_command_displays_docs_link(): void
    {
        $output = $this->runAndCaptureOutput();

        $this->assertStringContainsString('tallcms.com/docs', $output);
    }
}
