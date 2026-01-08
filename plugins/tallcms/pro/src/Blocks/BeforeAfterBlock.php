<?php

namespace Tallcms\Pro\Blocks;

use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Tallcms\Pro\Traits\RequiresLicense;

class BeforeAfterBlock extends RichContentCustomBlock
{
    use RequiresLicense;

    public static function getId(): string
    {
        return 'pro-before-after';
    }

    public static function getLabel(): string
    {
        return 'Before/After (Pro)';
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalDescription('Interactive image comparison slider')
            ->modalHeading('Configure Before/After Block')
            ->modalWidth('2xl')
            ->schema([
                Section::make('Header')
                    ->schema([
                        TextInput::make('heading')
                            ->label('Section Heading')
                            ->placeholder('See the Difference'),

                        Textarea::make('subheading')
                            ->label('Subheading')
                            ->placeholder('Drag the slider to compare')
                            ->rows(2),
                    ]),

                Section::make('Images')
                    ->schema([
                        TextInput::make('before_image')
                            ->label('Before Image URL')
                            ->placeholder('https://example.com/before.jpg')
                            ->required()
                            ->helperText('The "before" image (shown on the left)'),

                        TextInput::make('before_label')
                            ->label('Before Label')
                            ->placeholder('Before')
                            ->default('Before'),

                        TextInput::make('after_image')
                            ->label('After Image URL')
                            ->placeholder('https://example.com/after.jpg')
                            ->required()
                            ->helperText('The "after" image (shown on the right)'),

                        TextInput::make('after_label')
                            ->label('After Label')
                            ->placeholder('After')
                            ->default('After'),
                    ])
                    ->columns(2),

                Section::make('Options')
                    ->schema([
                        TextInput::make('initial_position')
                            ->label('Initial Slider Position')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->default(50)
                            ->suffix('%')
                            ->helperText('Where the slider starts (0-100)'),

                        Select::make('orientation')
                            ->label('Slider Orientation')
                            ->options([
                                'horizontal' => 'Horizontal (Left/Right)',
                                'vertical' => 'Vertical (Top/Bottom)',
                            ])
                            ->default('horizontal'),

                        Select::make('width')
                            ->label('Max Width')
                            ->options([
                                'full' => 'Full Width',
                                'xl' => 'Extra Large (1280px)',
                                'lg' => 'Large (1024px)',
                                'md' => 'Medium (768px)',
                            ])
                            ->default('xl'),

                        Toggle::make('show_labels')
                            ->label('Show Labels')
                            ->default(true),

                        Toggle::make('rounded')
                            ->label('Rounded Corners')
                            ->default(true),
                    ])
                    ->columns(3),

                Section::make('Caption')
                    ->schema([
                        Textarea::make('caption')
                            ->label('Caption')
                            ->placeholder('Optional description below the comparison')
                            ->rows(2),
                    ]),
            ])->slideOver();
    }

    public static function toPreviewHtml(array $config): string
    {
        $html = view('tallcms-pro::blocks.before-after', [
            'heading' => $config['heading'] ?? '',
            'subheading' => $config['subheading'] ?? '',
            'before_image' => $config['before_image'] ?? '',
            'before_label' => $config['before_label'] ?? 'Before',
            'after_image' => $config['after_image'] ?? '',
            'after_label' => $config['after_label'] ?? 'After',
            'initial_position' => $config['initial_position'] ?? 50,
            'orientation' => $config['orientation'] ?? 'horizontal',
            'width' => $config['width'] ?? 'xl',
            'show_labels' => $config['show_labels'] ?? true,
            'rounded' => $config['rounded'] ?? true,
            'caption' => $config['caption'] ?? '',
            'is_preview' => true,
        ])->render();

        return static::wrapWithLicenseCheck($html);
    }

    public static function toHtml(array $config, array $data): string
    {
        $html = view('tallcms-pro::blocks.before-after', [
            'heading' => $config['heading'] ?? '',
            'subheading' => $config['subheading'] ?? '',
            'before_image' => $config['before_image'] ?? '',
            'before_label' => $config['before_label'] ?? 'Before',
            'after_image' => $config['after_image'] ?? '',
            'after_label' => $config['after_label'] ?? 'After',
            'initial_position' => $config['initial_position'] ?? 50,
            'orientation' => $config['orientation'] ?? 'horizontal',
            'width' => $config['width'] ?? 'xl',
            'show_labels' => $config['show_labels'] ?? true,
            'rounded' => $config['rounded'] ?? true,
            'caption' => $config['caption'] ?? '',
            'is_preview' => false,
        ])->render();

        return static::wrapWithLicenseCheck($html);
    }
}
