<?php

namespace TallCms\Cms\Tests\Unit;

use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor\RichEditorTool;
use TallCms\Cms\Filament\Forms\Components\Actions\InsertMediaAction;
use TallCms\Cms\Filament\Forms\Components\Plugins\MediaLibraryPlugin;
use TallCms\Cms\Tests\TestCase;

class MediaLibraryPluginTest extends TestCase
{
    public function test_plugin_returns_insert_media_tool(): void
    {
        $plugin = MediaLibraryPlugin::make();
        $tools = $plugin->getEditorTools();

        $this->assertCount(1, $tools);
        $this->assertInstanceOf(RichEditorTool::class, $tools[0]);
        $this->assertSame('insertMedia', $tools[0]->getName());
    }

    public function test_plugin_returns_insert_media_action(): void
    {
        $plugin = MediaLibraryPlugin::make();
        $actions = $plugin->getEditorActions();

        $this->assertCount(1, $actions);
        $this->assertInstanceOf(Action::class, $actions[0]);
        $this->assertSame('insertMedia', $actions[0]->getName());
    }

    public function test_insert_media_action_default_name(): void
    {
        $action = InsertMediaAction::make();

        $this->assertSame('insertMedia', $action->getName());
    }

    public function test_plugin_returns_no_extensions(): void
    {
        $plugin = MediaLibraryPlugin::make();

        $this->assertSame([], $plugin->getTipTapPhpExtensions());
        $this->assertSame([], $plugin->getTipTapJsExtensions());
    }
}
