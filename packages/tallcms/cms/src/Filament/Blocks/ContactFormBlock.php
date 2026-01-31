<?php

namespace TallCms\Cms\Filament\Blocks;

use TallCms\Cms\Filament\Blocks\Concerns\HasAnimationOptions;
use TallCms\Cms\Filament\Blocks\Concerns\HasBlockIdentifiers;
use TallCms\Cms\Filament\Blocks\Concerns\HasBlockMetadata;
use TallCms\Cms\Filament\Blocks\Concerns\HasContentWidth;
use TallCms\Cms\Filament\Blocks\Concerns\HasDaisyUIOptions;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;

class ContactFormBlock extends RichContentCustomBlock
{
    use HasAnimationOptions;
    use HasBlockIdentifiers;
    use HasBlockMetadata;
    use HasContentWidth;
    use HasDaisyUIOptions;

    protected static function getDefaultWidth(): string
    {
        return 'narrow';
    }

    public static function getCategory(): string
    {
        return 'forms';
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-envelope';
    }

    public static function getDescription(): string
    {
        return 'Contact form with customizable fields';
    }

    public static function getKeywords(): array
    {
        return ['contact', 'email', 'form', 'message'];
    }

    public static function getSortPriority(): int
    {
        return 10;
    }
    public static function getId(): string
    {
        return 'contact_form';
    }

    public static function getLabel(): string
    {
        return 'Contact Form';
    }

    public static function getDefaultFields(): array
    {
        return [
            ['name' => 'name', 'type' => 'text', 'label' => 'Name', 'required' => true, 'options' => []],
            ['name' => 'email', 'type' => 'email', 'label' => 'Email', 'required' => true, 'options' => []],
            ['name' => 'message', 'type' => 'textarea', 'label' => 'Message', 'required' => true, 'options' => []],
        ];
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalDescription('Configure your contact form fields and settings')
            ->modalWidth('6xl')
            ->schema([
                Tabs::make('Contact Form Configuration')
                    ->tabs([
                        Tab::make('Content')
                            ->icon('heroicon-m-queue-list')
                            ->schema([
                                Section::make('Form Header')
                                    ->description('Optional title and description shown above the form')
                                    ->schema([
                                        TextInput::make('title')
                                            ->maxLength(255)
                                            ->placeholder('e.g., Get in Touch'),

                                        Textarea::make('description')
                                            ->maxLength(500)
                                            ->placeholder('e.g., Fill out the form below and we\'ll get back to you shortly.'),
                                    ])
                                    ->collapsible(),

                                Section::make('Form Fields')
                                    ->description('Configure which fields appear in your form')
                                    ->schema([
                                        Repeater::make('fields')
                                            ->label('')
                                            ->schema([
                                                Select::make('type')
                                                    ->label('Field Type')
                                                    ->options([
                                                        'text' => 'Text',
                                                        'email' => 'Email',
                                                        'tel' => 'Phone',
                                                        'textarea' => 'Text Area',
                                                        'select' => 'Dropdown',
                                                    ])
                                                    ->required()
                                                    ->live(),

                                                TextInput::make('name')
                                                    ->label('Field Name')
                                                    ->required()
                                                    ->alphaNum()
                                                    ->maxLength(50)
                                                    ->distinct()
                                                    ->helperText('Unique identifier (no spaces)'),

                                                TextInput::make('label')
                                                    ->label('Display Label')
                                                    ->required()
                                                    ->maxLength(255),

                                                Toggle::make('required')
                                                    ->label('Required')
                                                    ->default(false)
                                                    ->inline(false),

                                                TagsInput::make('options')
                                                    ->label('Dropdown Options')
                                                    ->visible(fn (Get $get): bool => $get('type') === 'select')
                                                    ->helperText('Press Enter after each option (max 50 options, 100 chars each)')
                                                    ->nestedRecursiveRules([
                                                        'string',
                                                        'max:100',
                                                    ])
                                                    ->rules(['array', 'max:50'])
                                                    ->dehydrateStateUsing(fn (?array $state): ?array => $state
                                                        ? array_values(array_filter(array_map('trim', $state), fn ($v) => $v !== ''))
                                                        : null
                                                    )
                                                    ->columnSpanFull(),
                                            ])
                                            ->columns(2)
                                            ->defaultItems(0)
                                            ->default(self::getDefaultFields())
                                            ->minItems(1)
                                            ->maxItems(20)
                                            ->reorderable()
                                            ->reorderableWithDragAndDrop()
                                            ->collapsible()
                                            ->itemLabel(fn (array $state): ?string => ($state['label'] ?? 'Field').' ('.($state['type'] ?? 'text').')')
                                            ->addActionLabel('Add Field'),
                                    ]),

                                Section::make('Form Settings')
                                    ->description('Customize button text and success message')
                                    ->schema([
                                        TextInput::make('submit_button_text')
                                            ->label('Submit Button Text')
                                            ->default('Send Message')
                                            ->maxLength(50),

                                        Textarea::make('success_message')
                                            ->label('Success Message')
                                            ->default('Thank you for your message! We\'ll be in touch soon.')
                                            ->maxLength(500)
                                            ->helperText('Shown after successful form submission'),
                                    ])
                                    ->collapsible(),
                            ]),

                        Tab::make('Appearance')
                            ->icon('heroicon-m-paint-brush')
                            ->schema([
                                static::getContentWidthField(),

                                Select::make('button_style')
                                    ->label('Submit Button Style')
                                    ->options(static::getButtonVariantOptions())
                                    ->default('btn-primary'),

                                Select::make('background')
                                    ->label('Background')
                                    ->options(static::getBackgroundOptions())
                                    ->default('bg-base-100'),

                                Select::make('padding')
                                    ->label('Section Padding')
                                    ->options(static::getPaddingOptions())
                                    ->default('py-16'),

                                Toggle::make('first_section')
                                    ->label('First Section (Remove Top Padding)')
                                    ->helperText('Overrides padding setting above')
                                    ->default(false),
                            ]),

                        Tab::make('Animation')
                            ->icon('heroicon-m-sparkles')
                            ->schema([
                                Select::make('animation_type')
                                    ->label('Entrance Animation')
                                    ->options(static::getAnimationTypeOptions())
                                    ->default('')
                                    ->helperText('Animation plays when block scrolls into view'),

                                Select::make('animation_duration')
                                    ->label('Animation Speed')
                                    ->options(static::getAnimationDurationOptions())
                                    ->default('anim-duration-700'),
                            ])
                            ->columns(2),
                    ]),

                static::getIdentifiersSection(),
            ])
            ->slideOver();
    }

    private static function normalizeConfig(array $config): array
    {
        $fields = isset($config['fields']) && is_array($config['fields'])
            ? array_values($config['fields'])
            : self::getDefaultFields();

        return array_merge([
            'title' => '',
            'description' => '',
            'fields' => self::getDefaultFields(),
            'submit_button_text' => 'Send Message',
            'success_message' => 'Thank you for your message! We\'ll be in touch soon.',
            'button_style' => 'btn-primary',
            'background' => 'bg-base-100',
            'padding' => 'py-16',
            'first_section' => false,
        ], $config, ['fields' => $fields]);
    }

    public static function toPreviewHtml(array $config): string
    {
        return static::renderBlock($config, 'tallcms::cms.blocks.contact-form-preview');
    }

    public static function toHtml(array $config, array $data): string
    {
        return static::renderBlock($config, 'tallcms::cms.blocks.contact-form');
    }

    protected static function renderBlock(array $config, string $view): string
    {
        $normalizedConfig = self::normalizeConfig($config);
        $widthConfig = static::resolveWidthClass($config);
        $animConfig = static::getAnimationConfig($config);

        return view($view, [
            'config' => $normalizedConfig,
            'contentWidthClass' => $widthConfig['class'],
            'contentPadding' => $widthConfig['padding'],
            'anchor_id' => static::getAnchorId($config, $config['title'] ?? null),
            'css_classes' => static::getCssClasses($config),
            'animation_type' => $animConfig['animation_type'],
            'animation_duration' => $animConfig['animation_duration'],
        ])->render();
    }
}
