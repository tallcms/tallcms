<?php

declare(strict_types=1);

namespace TallCms\Cms\Console\Commands;

use Illuminate\Console\Command;

class TallCmsPostInstall extends Command
{
    use Concerns\HasAsciiBanner;

    protected $signature = 'tallcms:post-install';

    protected $description = 'Display post-installation welcome message';

    protected $hidden = true;

    public function handle(): int
    {
        $this->displayHeader();

        $this->info('TallCMS installed successfully!');
        $this->newLine();

        $dir = basename(getcwd());
        $this->info('Next steps:');
        $this->line("  1. cd {$dir}");
        $this->line('  2. npm install && npm run build');
        $this->line('  3. php artisan serve');
        $this->line('  4. Visit <fg=cyan>http://localhost:8000/install</> to complete setup');
        $this->newLine();

        $this->line('  <fg=gray>Documentation: https://tallcms.com/docs</>');
        $this->newLine();

        return Command::SUCCESS;
    }
}
