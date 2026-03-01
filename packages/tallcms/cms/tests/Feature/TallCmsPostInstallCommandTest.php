<?php

namespace TallCms\Cms\Tests\Feature;

use TallCms\Cms\Tests\TestCase;

class TallCmsPostInstallCommandTest extends TestCase
{
    public function test_post_install_command_exits_with_success(): void
    {
        $this->artisan('tallcms:post-install')
            ->assertExitCode(0);
    }

    public function test_post_install_command_displays_success_message(): void
    {
        $this->artisan('tallcms:post-install')
            ->expectsOutputToContain('TallCMS installed successfully!')
            ->assertExitCode(0);
    }

    public function test_post_install_command_displays_next_steps(): void
    {
        $this->artisan('tallcms:post-install')
            ->expectsOutputToContain('Next steps')
            ->expectsOutputToContain('npm install')
            ->assertExitCode(0);
    }

    public function test_post_install_command_displays_install_url(): void
    {
        $this->artisan('tallcms:post-install')
            ->expectsOutputToContain('localhost:8000/install')
            ->assertExitCode(0);
    }

    public function test_post_install_command_displays_docs_link(): void
    {
        $this->artisan('tallcms:post-install')
            ->expectsOutputToContain('tallcms.com/docs')
            ->assertExitCode(0);
    }
}
