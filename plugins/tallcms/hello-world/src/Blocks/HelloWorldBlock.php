<?php

namespace Tallcms\HelloWorld\Blocks;

use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;

class HelloWorldBlock extends RichContentCustomBlock
{
    public static function getId(): string
    {
        return 'hello-world';
    }

    public static function getLabel(): string
    {
        return 'Hello World';
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalDescription('Add a customizable greeting block to your content')
            ->modalHeading('Configure Hello World Block')
            ->modalWidth('lg')
            ->schema([
                Section::make('Content')
                    ->schema([
                        TextInput::make('greeting')
                            ->label('Greeting Text')
                            ->placeholder('Hello, World!')
                            ->default('Hello, World!')
                            ->required(),

                        Textarea::make('message')
                            ->label('Message')
                            ->placeholder('Enter your message here...')
                            ->default('This block is provided by the Hello World plugin.')
                            ->rows(3),
                    ]),

                Section::make('Style')
                    ->schema([
                        Select::make('style')
                            ->label('Block Style')
                            ->options([
                                'default' => 'Default',
                                'gradient' => 'Gradient Background',
                                'bordered' => 'Bordered',
                                'minimal' => 'Minimal',
                            ])
                            ->default('default'),

                        Select::make('alignment')
                            ->label('Text Alignment')
                            ->options([
                                'left' => 'Left',
                                'center' => 'Center',
                                'right' => 'Right',
                            ])
                            ->default('center'),
                    ])
                    ->columns(2),
            ])->slideOver();
    }

    public static function toPreviewHtml(array $config): string
    {
        return view('tallcms-helloworld::blocks.hello-world', [
            'id' => static::getId(),
            'greeting' => $config['greeting'] ?? 'Hello, World!',
            'message' => $config['message'] ?? 'This block is provided by the Hello World plugin.',
            'style' => $config['style'] ?? 'default',
            'alignment' => $config['alignment'] ?? 'center',
        ])->render();
    }

    public static function toHtml(array $config, array $data): string
    {
        return view('tallcms-helloworld::blocks.hello-world', [
            'id' => static::getId(),
            'greeting' => $config['greeting'] ?? 'Hello, World!',
            'message' => $config['message'] ?? 'This block is provided by the Hello World plugin.',
            'style' => $config['style'] ?? 'default',
            'alignment' => $config['alignment'] ?? 'center',
        ])->render();
    }
}
