<?php

namespace TallCms\Cms\Filament\Blocks;

use TallCms\Cms\Filament\Blocks\Concerns\HasDaisyUIOptions;
use Filament\Actions\Action;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;

class ParallaxBlock extends RichContentCustomBlock
{
    use HasDaisyUIOptions;

    public static function getId(): string
    {
        return 'parallax';
    }

    public static function getLabel(): string
    {
        return 'Parallax Section';
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalDescription('Create a full-width parallax image section with overlay content')
            ->modalHeading('Configure Parallax Section')
            ->modalWidth('5xl')
            ->schema([
                Tabs::make('Parallax Configuration')
                    ->tabs([
                        Tab::make('Content')
                            ->icon('heroicon-m-photo')
                            ->schema([
                                FileUpload::make('image')
                                    ->label('Background Image')
                                    ->required()
                                    ->image()
                                    ->disk(\cms_media_disk())
                                    ->directory('parallax')
                                    ->visibility(\cms_media_visibility())
                                    ->imageResizeMode('cover')
                                    ->imageResizeTargetWidth('1920')
                                    ->imageResizeTargetHeight('1080'),

                                TextInput::make('heading')
                                    ->label('Heading')
                                    ->placeholder('Your Inspiring Message')
                                    ->maxLength(255),

                                Textarea::make('subheading')
                                    ->label('Subheading')
                                    ->placeholder('A brief description or call to action')
                                    ->maxLength(500)
                                    ->rows(2),

                                TextInput::make('cta_text')
                                    ->label('Button Text (Optional)')
                                    ->placeholder('Get Started')
                                    ->maxLength(50),

                                TextInput::make('cta_url')
                                    ->label('Button URL')
                                    ->placeholder('https://example.com or /page')
                                    ->maxLength(500),
                            ]),

                        Tab::make('Appearance')
                            ->icon('heroicon-m-paint-brush')
                            ->schema([
                                Section::make('Layout')
                                    ->schema([
                                        Select::make('height')
                                            ->label('Section Height')
                                            ->options([
                                                'small' => 'Small (300px)',
                                                'medium' => 'Medium (500px)',
                                                'large' => 'Large (700px)',
                                                'full' => 'Full Viewport',
                                            ])
                                            ->default('medium'),

                                        Select::make('text_alignment')
                                            ->label('Text Alignment')
                                            ->options(static::getTextAlignmentOptions())
                                            ->default('text-center'),
                                    ])
                                    ->columns(2),

                                Section::make('Overlay')
                                    ->schema([
                                        ColorPicker::make('overlay_color')
                                            ->label('Overlay Color')
                                            ->default('#000000'),

                                        Select::make('overlay_opacity')
                                            ->label('Overlay Opacity')
                                            ->options([
                                                '0' => '0% (No Overlay)',
                                                '10' => '10%',
                                                '20' => '20%',
                                                '30' => '30%',
                                                '40' => '40%',
                                                '50' => '50%',
                                                '60' => '60%',
                                                '70' => '70%',
                                                '80' => '80%',
                                            ])
                                            ->default('50'),
                                    ])
                                    ->columns(2),
                            ]),
                    ]),
            ])->slideOver();
    }

    public static function toPreviewHtml(array $config): string
    {
        return static::renderBlock(array_merge($config, [
            'heading' => $config['heading'] ?? 'Inspiring Parallax Section',
            'subheading' => $config['subheading'] ?? 'Create beautiful, immersive experiences with parallax scrolling',
        ]));
    }

    public static function toHtml(array $config, array $data): string
    {
        // Don't render if no image
        if (empty($config['image'])) {
            return '';
        }

        return static::renderBlock($config);
    }

    protected static function renderBlock(array $config): string
    {
        return view('tallcms::cms.blocks.parallax', [
            'id' => static::getId(),
            'image' => $config['image'] ?? null,
            'heading' => $config['heading'] ?? '',
            'subheading' => $config['subheading'] ?? '',
            'cta_text' => $config['cta_text'] ?? '',
            'cta_url' => $config['cta_url'] ?? '',
            'height' => $config['height'] ?? 'medium',
            'text_alignment' => $config['text_alignment'] ?? 'text-center',
            'overlay_color' => $config['overlay_color'] ?? '#000000',
            'overlay_opacity' => $config['overlay_opacity'] ?? '50',
        ])->render();
    }
}
