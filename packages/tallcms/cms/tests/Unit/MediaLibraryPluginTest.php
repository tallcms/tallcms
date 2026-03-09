<?php

namespace TallCms\Cms\Tests\Unit;

use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\RichEditor\EditorCommand;
use Filament\Forms\Components\RichEditor\RichEditorTool;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Mockery;
use TallCms\Cms\Filament\Forms\Components\Actions\InsertMediaAction;
use TallCms\Cms\Filament\Forms\Components\Plugins\MediaLibraryPlugin;
use TallCms\Cms\Models\TallcmsMedia;
use TallCms\Cms\Tests\TestCase;

class MediaLibraryPluginTest extends TestCase
{
    use RefreshDatabase;
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

    public function test_action_inserts_image_content_with_correct_payload(): void
    {
        Storage::fake('public');

        $media = TallcmsMedia::create([
            'name' => 'test-photo',
            'file_name' => 'test-photo.jpg',
            'mime_type' => 'image/jpeg',
            'path' => 'cms/test-photo.jpg',
            'disk' => 'public',
            'size' => 12345,
            'alt_text' => 'A test photo',
        ]);

        $capturedCommands = null;
        $capturedSelection = null;

        $component = Mockery::mock(RichEditor::class);
        $component->shouldReceive('runCommands')
            ->once()
            ->withArgs(function (array $commands, ?array $editorSelection) use (&$capturedCommands, &$capturedSelection) {
                $capturedCommands = $commands;
                $capturedSelection = $editorSelection;

                return true;
            });

        $action = InsertMediaAction::make();

        // Extract and invoke the action closure
        $reflection = new \ReflectionClass($action);
        $property = $reflection->getProperty('action');
        $closure = $property->getValue($action);

        $closure(
            arguments: ['editorSelection' => ['type' => 'text', 'anchor' => 5]],
            data: ['selected_media_id' => $media->id, 'alt' => 'Custom alt text'],
            component: $component,
        );

        $this->assertCount(1, $capturedCommands);
        $this->assertInstanceOf(EditorCommand::class, $capturedCommands[0]);
        $this->assertSame('insertContent', $capturedCommands[0]->name);

        $content = $capturedCommands[0]->arguments[0];
        $this->assertSame('image', $content['type']);
        $this->assertSame($media->id, $content['attrs']['id']);
        $this->assertSame($media->url, $content['attrs']['src']);
        $this->assertSame('Custom alt text', $content['attrs']['alt']);
        $this->assertSame(['type' => 'text', 'anchor' => 5], $capturedSelection);
    }

    public function test_action_falls_back_to_media_alt_text(): void
    {
        Storage::fake('public');

        $media = TallcmsMedia::create([
            'name' => 'fallback-photo',
            'file_name' => 'fallback-photo.jpg',
            'mime_type' => 'image/jpeg',
            'path' => 'cms/fallback-photo.jpg',
            'disk' => 'public',
            'size' => 5000,
            'alt_text' => 'Media alt text',
        ]);

        $capturedCommands = null;

        $component = Mockery::mock(RichEditor::class);
        $component->shouldReceive('runCommands')
            ->once()
            ->withArgs(function (array $commands) use (&$capturedCommands) {
                $capturedCommands = $commands;

                return true;
            });

        $action = InsertMediaAction::make();
        $reflection = new \ReflectionClass($action);
        $property = $reflection->getProperty('action');
        $closure = $property->getValue($action);

        $closure(
            arguments: [],
            data: ['selected_media_id' => $media->id, 'alt' => ''],
            component: $component,
        );

        $content = $capturedCommands[0]->arguments[0];
        $this->assertSame('Media alt text', $content['attrs']['alt']);
    }

    public function test_action_does_nothing_for_missing_media(): void
    {
        $component = Mockery::mock(RichEditor::class);
        $component->shouldNotReceive('runCommands');

        $action = InsertMediaAction::make();
        $reflection = new \ReflectionClass($action);
        $property = $reflection->getProperty('action');
        $closure = $property->getValue($action);

        $closure(
            arguments: [],
            data: ['selected_media_id' => 99999, 'alt' => 'whatever'],
            component: $component,
        );
    }
}
