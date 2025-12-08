<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakeTallCmsBlock extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:tallcms-block {name : The name of the custom block}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new TallCMS custom block with hybrid styling approach';

    /**
     * The filesystem instance.
     */
    protected Filesystem $files;

    /**
     * Create a new command instance.
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = $this->argument('name');
        $className = Str::studly($name) . 'Block';
        $kebabName = Str::kebab($name);
        $snakeName = Str::snake($name);
        
        // Create the block class
        $this->createBlockClass($className, $kebabName, $snakeName);
        
        // Create the template
        $this->createBlockTemplate($kebabName, $className);
        
        $this->info("TallCMS block '{$className}' created successfully!");
        $this->info("Block class: app/Filament/Forms/Components/RichEditor/RichContentCustomBlocks/{$className}.php");
        $this->info("Template: resources/views/cms/blocks/{$kebabName}.blade.php");
        $this->newLine();
        $this->info("✅ No registration required! The block will be auto-discovered and available immediately.");
        $this->line("🎉 Your new block is ready to use in the rich editor!");
    }

    /**
     * Create the block class file.
     */
    protected function createBlockClass(string $className, string $kebabName, string $snakeName): void
    {
        $classPath = app_path("Filament/Forms/Components/RichEditor/RichContentCustomBlocks/{$className}.php");
        
        // Ensure directory exists
        $this->files->ensureDirectoryExists(dirname($classPath));
        
        $stub = $this->getBlockClassStub();
        $content = str_replace(
            ['{{CLASS_NAME}}', '{{KEBAB_NAME}}', '{{SNAKE_NAME}}', '{{BLOCK_ID}}'],
            [$className, $kebabName, $snakeName, $snakeName],
            $stub
        );
        
        $this->files->put($classPath, $content);
    }

    /**
     * Create the block template file.
     */
    protected function createBlockTemplate(string $kebabName, string $className): void
    {
        $templatePath = resource_path("views/cms/blocks/{$kebabName}.blade.php");
        
        // Ensure directory exists
        $this->files->ensureDirectoryExists(dirname($templatePath));
        
        $stub = $this->getBlockTemplateStub();
        $content = str_replace(
            ['{{KEBAB_NAME}}', '{{CLASS_NAME}}'],
            [$kebabName, $className],
            $stub
        );
        
        $this->files->put($templatePath, $content);
    }

    /**
     * Get the block class stub.
     */
    protected function getBlockClassStub(): string
    {
        return <<<'EOT'
<?php

namespace App\Filament\Forms\Components\RichEditor\RichContentCustomBlocks;

use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;

class {{CLASS_NAME}} extends RichContentCustomBlock
{
    public static function getId(): string
    {
        return '{{BLOCK_ID}}';
    }

    public static function getLabel(): string
    {
        return '{{CLASS_NAME}}';
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalDescription('Configure the {{SNAKE_NAME}} block')
            ->schema([
                TextInput::make('title')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('Enter block title'),
                    
                Textarea::make('description')
                    ->maxLength(500)
                    ->placeholder('Enter block description'),
            ])->slideOver();
    }

    public static function toPreviewHtml(array $config): string
    {
        return view('cms.blocks.{{KEBAB_NAME}}', [
            'title' => $config['title'] ?? 'Sample Title',
            'description' => $config['description'] ?? 'Sample description text',
        ])->render();
    }

    public static function toHtml(array $config, array $data): string
    {
        return view('cms.blocks.{{KEBAB_NAME}}', [
            'title' => $config['title'] ?? '',
            'description' => $config['description'] ?? '',
        ])->render();
    }
}
EOT;
    }

    /**
     * Get the block template stub.
     */
    protected function getBlockTemplateStub(): string
    {
        return <<<'EOT'
{{-- {{CLASS_NAME}} - Generated by TallCMS --}}
<div class="py-8 px-4 max-w-4xl mx-auto" 
     style="padding: 2rem 1rem; max-width: 56rem; margin: 0 auto;">
    @if($title)
        <h2 class="text-2xl font-bold text-gray-900 mb-4" 
            style="font-size: 1.5rem; font-weight: bold; color: #111827; margin-bottom: 1rem;">
            {{ $title }}
        </h2>
    @endif
    
    @if($description)
        <div class="text-lg text-gray-700 leading-relaxed" 
             style="font-size: 1.125rem; color: #374151; line-height: 1.75;">
            {!! nl2br(e($description)) !!}
        </div>
    @endif
</div>

{{-- 
    TallCMS Hybrid Styling Approach:
    - Tailwind classes provide primary styling and responsiveness
    - Inline styles ensure consistent rendering in admin previews
    - Both work together for maximum compatibility
    
    To customize this block:
    1. Add more form fields in the configureEditorAction method
    2. Update both toPreviewHtml and toHtml methods with new variables
    3. Modify this template to match your design requirements
    4. Follow the hybrid styling pattern (classes + inline styles)
--}}
EOT;
    }
}
