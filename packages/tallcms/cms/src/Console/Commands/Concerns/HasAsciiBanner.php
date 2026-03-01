<?php

declare(strict_types=1);

namespace TallCms\Cms\Console\Commands\Concerns;

trait HasAsciiBanner
{
    /**
     * Display a colored ASCII art "TALLCMS" banner.
     *
     * Only renders when output is decorated (supports ANSI) and input is interactive.
     * Skipped entirely in CI, piped output, or non-TTY terminals.
     */
    protected function displayHeader(): void
    {
        if (! $this->output->isDecorated() || ! $this->input->isInteractive()) {
            return;
        }

        $lines = [
            '████████╗ █████╗ ██╗     ██╗      ██████╗███╗   ███╗███████╗',
            '╚══██╔══╝██╔══██╗██║     ██║     ██╔════╝████╗ ████║██╔════╝',
            '   ██║   ███████║██║     ██║     ██║     ██╔████╔██║███████╗',
            '   ██║   ██╔══██║██║     ██║     ██║     ██║╚██╔╝██║╚════██║',
            '   ██║   ██║  ██║███████╗███████╗╚██████╗██║ ╚═╝ ██║███████║',
            '   ╚═╝   ╚═╝  ╚═╝╚══════╝╚══════╝ ╚═════╝╚═╝     ╚═╝╚══════╝',
        ];

        $gradients = [
            // Ocean
            [33, 39, 45, 51, 87, 123],
            // Vaporwave
            [201, 165, 129, 93, 57, 21],
            // Sunset
            [196, 202, 208, 214, 220, 226],
            // Forest
            [22, 28, 34, 40, 46, 82],
            // Coral
            [196, 197, 198, 199, 200, 201],
            // Amber
            [130, 172, 214, 220, 226, 228],
        ];

        $gradient = $gradients[array_rand($gradients)];

        $this->newLine();
        foreach ($lines as $i => $line) {
            $color = $gradient[$i] ?? $gradient[array_key_last($gradient)];
            $this->output->writeln("  \e[38;5;{$color}m{$line}\e[0m");
        }
        $this->newLine();
    }
}
