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

        $this->components->info('TallCMS installed successfully!');
        $this->newLine();

        $dir = basename(getcwd());
        $this->components->info('Next steps:');
        $this->components->bulletList([
            "cd {$dir}",
            'npm install && npm run build',
            'php artisan serve',
            'Visit <fg=cyan>http://localhost:8000/install</> to complete setup',
        ]);
        $this->newLine();

        $this->line('  <fg=gray>Documentation: https://tallcms.com/docs</>');
        $this->newLine();

        return Command::SUCCESS;
    }
}
