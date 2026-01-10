<?php

namespace Wsmallnews\FilamentNestedset\Commands;

use Filament\Clusters\Cluster;
use Filament\Facades\Filament;
use Filament\Panel;
use Filament\Support\Commands\Concerns\CanIndentStrings;
use Filament\Support\Commands\Concerns\CanManipulateFiles;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Symfony\Component\Console\Attribute\AsCommand;

use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

#[AsCommand(name: 'make:filament-nestedset-page')]
class MakeNestedsetPageCommand extends Command
{
    use CanIndentStrings;
    use CanManipulateFiles;

    protected $description = 'Create a new Filament Nestedset Page';

    public $signature = 'make:filament-nestedset-page {name?} {model?} {--panel=} {--F|force}';

    public function handle(Filesystem $filesystem): int
    {
        $page = $this->parsePage();

        $pageClass = (string) str($page)->afterLast('\\');
        $pageNamespace = str($page)->contains('\\') ? (string) str($page)->beforeLast('\\') : '';

        $panel = $this->getPanel();

        // 文件要保存的 命名空间，和 目录
        ['namespace' => $namespace, 'path' => $path] = $this->getPanelNamespaceAndPath($panel);

        // 页面最终保存路径
        $path = (string) str($page)
            ->prepend('/')
            ->prepend($path)
            ->replace('\\', '/')
            ->replace('//', '/')
            ->append('.php');

        if (! $this->option('force') && $this->checkForCollision([$path])) {
            return self::INVALID;
        }

        // cluster 需要替换的内容
        $clusterParams = $this->getPotentialCluster($namespace);

        // model 替换内容
        $model = $this->getModelStmt();

        $this->copyStub(
            filesystem: $filesystem,
            targetPath: $path,
            replacements: [
                'class' => $pageClass,
                ...$clusterParams,
                'namespace' => $namespace . ($pageNamespace !== '' ? "\\{$pageNamespace}" : ''),
                'model' => $model,
            ],
        );

        $this->components->info("Admin tree page [{$path}] created successfully.");

        return self::SUCCESS;
    }

    /**
     * 解析页面名称
     */
    public function parsePage(): string
    {
        $pageName = $this->argument('name') ?? text(
            label: 'What is the nestedset page name?',
            placeholder: 'NestedsetPage',
            required: true,
        );

        if (preg_match('([^A-Za-z0-9_/\\\\])', $pageName)) {
            throw new InvalidArgumentException('Page name contains invalid characters.');
        }

        return (string) str($pageName)
            ->trim('\\/')
            ->trim(' ')
            ->replace('/', '\\');
    }

    /**
     * 获取选择的 panel
     */
    private function getPanel(): Panel
    {
        $panel = $this->option('panel');

        if ($panel) {
            return Filament::getPanel($panel, isStrict: false);
        }

        $panels = Filament::getPanels();

        if (count($panels) === 1) {
            return Arr::first($panels);
        }

        $panelIndex = select(
            label: 'Which panel would you like to create this in?',
            options: array_map(
                static fn (Panel $panel): string => $panel->getId(),
                $panels,
            ),
            default: Filament::getDefaultPanel()->getId(),
        );

        return $panels[$panelIndex];
    }

    /**
     * 获取要创建的页面所在的 命名空间 和 目录
     *
     * @return array
     */
    private function getPanelNamespaceAndPath(Panel $panel)
    {
        $pageDirectories = $panel->getPageDirectories();
        $pageNamespaces = $panel->getPageNamespaces();

        // 如果是 vendor 下面的目录，则排除 vendor 目录 （不可创建到 vendor 目录下）
        foreach ($pageDirectories as $pageIndex => $pageDirectory) {
            if (str($pageDirectory)->startsWith(base_path('vendor'))) {
                unset($pageDirectories[$pageIndex], $pageNamespaces[$pageIndex]);
            }
        }

        $namespace = (\count($pageNamespaces) > 1)
            ? select(
                label: 'Which namespace would you like to create this in?',
                options: $pageNamespaces,
            )
            : (Arr::first($pageNamespaces) ?? 'App\\Filament\\Pages');

        $path = (\count($pageDirectories) > 1)
            ? $pageDirectories[array_search($namespace, $pageNamespaces, true)]
            : (Arr::first($pageDirectories) ?? app_path('Filament/Pages/'));

        return compact('namespace', 'path');
    }

    /**
     * 获取 cluster 需要填充的内容
     *
     * @param  string  $namespace
     * @return array
     */
    private function getPotentialCluster($namespace)
    {
        $potentialCluster = (string) str($namespace)->beforeLast('\Pages');
        $clusterAssignment = null;
        $clusterImport = null;

        if (
            class_exists($potentialCluster)
            && is_subclass_of($potentialCluster, Cluster::class)
            && filled($potentialCluster)
        ) {
            $clusterAssignment = $this->indentString(
                PHP_EOL . PHP_EOL . 'protected static ?string $cluster = ' . class_basename(
                    $potentialCluster,
                ) . '::class;',
            );

            $clusterImport = "use {$potentialCluster};" . PHP_EOL;
        }

        return compact('clusterAssignment', 'clusterImport');
    }

    /**
     * 获取 mdoel 需要填充的 代码
     */
    private function getModelStmt(): string
    {
        $placeholder = 'null;       // TODO: Set nestedset model';

        $modelName = $this->argument('model') ?? text(
            label: 'What is the model class?',
            placeholder: 'NestedsetModel',
        );

        if (blank($modelName)) {
            return $placeholder;
        }

        $modelClass = $this->parseModel($modelName);

        if (! class_exists($modelClass)) {
            $this->warn("Model '{$modelClass}' not found");

            return $placeholder;
        }

        return "{$modelClass}::class;";
    }

    /**
     * 格式化 model
     */
    private function parseModel(string $model): string
    {
        if (preg_match('([^A-Za-z0-9_/\\\\])', $model)) {
            throw new InvalidArgumentException('Model name contains invalid characters.');
        }

        $model = (string) str($model)->ltrim('\\/')->replace('/', '\\');

        $rootNamespace = $this->laravel->getNamespace();

        if (Str::startsWith($model, $rootNamespace)) {
            return $model;
        }

        return '\\' . $rootNamespace . 'Models\\' . $model;
    }

    private function copyStub(
        Filesystem $filesystem,
        string $targetPath,
        array $replacements = [],
    ): void {
        $stubPath = base_path('stubs/NestedsetPage.stub');

        if (! $this->fileExists($stubPath)) {
            $stubPath = $this->getDefaultStubPath() . '/NestedsetPage.stub';
        }

        $stub = str($filesystem->get($stubPath));

        foreach ($replacements as $key => $replacement) {
            $stub = $stub->replace("{{ {$key} }}", $replacement);
        }

        $this->writeFile($targetPath, (string) $stub);
    }
}
