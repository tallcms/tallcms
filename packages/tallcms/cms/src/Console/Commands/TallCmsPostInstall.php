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
        $dir = basename(getcwd());

        echo PHP_EOL;
        echo '  TallCMS installed successfully!'.PHP_EOL;
        echo PHP_EOL;
        echo '  Next steps:'.PHP_EOL;
        echo "    1. cd {$dir}".PHP_EOL;
        echo '    2. npm install && npm run build'.PHP_EOL;
        echo '    3. php artisan serve'.PHP_EOL;
        echo '    4. Visit http://localhost:8000/install to complete setup'.PHP_EOL;
        echo PHP_EOL;
        echo '  Documentation: https://tallcms.com/docs'.PHP_EOL;
        echo PHP_EOL;

        return Command::SUCCESS;
    }
}
