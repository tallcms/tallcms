<?php

namespace TallCms\Cms\Filament\Blocks;

use TallCms\Cms\Filament\Blocks\Concerns\HasAnimationOptions;
use TallCms\Cms\Filament\Blocks\Concerns\HasBlockIdentifiers;
use TallCms\Cms\Filament\Blocks\Concerns\HasBlockMetadata;
use TallCms\Cms\Filament\Blocks\Concerns\HasContentWidth;
use TallCms\Cms\Filament\Blocks\Concerns\HasDaisyUIOptions;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;

class TeamBlock extends RichContentCustomBlock
{
    use HasAnimationOptions;
    use HasBlockIdentifiers;
    use HasBlockMetadata;
    use HasContentWidth;
    use HasDaisyUIOptions;

    protected static function getDefaultWidth(): string
    {
        return 'wide';
    }

    public static function getCategory(): string
    {
        return 'social-proof';
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-user-group';
    }

    public static function getDescription(): string
    {
        return 'Team member profiles with photos';
    }

    public static function getKeywords(): array
    {
        return ['team', 'staff', 'members', 'people'];
    }

    public static function getSortPriority(): int
    {
        return 20;
    }

    protected static function getTeamCardStyleOptions(): array
    {
        return [
            'card bg-base-200 shadow-lg' => 'Cards with Shadow',
            'card bg-base-100 border border-base-300' => 'Bordered',
            'p-4' => 'Minimal',
        ];
    }

    public static function getId(): string
    {
        return 'team';
    }

    public static function getLabel(): string
    {
        return 'Team';
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalDescription('Display team members with photos, roles, and social links')
            ->modalHeading('Configure Team Block')
            ->modalWidth('6xl')
            ->schema([
                Tabs::make('Team Configuration')
                    ->tabs([
                        Tab::make('Content')
                            ->icon('heroicon-m-users')
                            ->schema([
                                TextInput::make('heading')
                                    ->label('Section Heading')
                                    ->placeholder('Meet Our Team')
                                    ->maxLength(255),

                                Textarea::make('subheading')
                                    ->label('Section Subheading')
                                    ->placeholder('The talented people behind our success')
                                    ->maxLength(500)
                                    ->rows(2),

                                Repeater::make('members')
                                    ->label('Team Members')
                                    ->schema([
                                        TextInput::make('name')
                                            ->label('Name')
                                            ->required()
                                            ->placeholder('Jane Smith')
                                            ->maxLength(100),

                                        TextInput::make('role')
                                            ->label('Role / Title')
                                            ->required()
                                            ->placeholder('CEO & Founder')
                                            ->maxLength(150),

                                        FileUpload::make('photo')
                                            ->label('Photo')
                                            ->image()
                                            ->disk(\cms_media_disk())
                                            ->directory('team')
                                            ->visibility(\cms_media_visibility())
                                            ->imageEditor()
                                            ->imageResizeMode('cover')
                                            ->imageCropAspectRatio('1:1')
                                            ->imageResizeTargetWidth('400')
                                            ->imageResizeTargetHeight('400'),

                                        Textarea::make('bio')
                                            ->label('Short Bio')
                                            ->placeholder('A brief description about this team member...')
                                            ->maxLength(500)
                                            ->rows(3),

                                        Repeater::make('social_links')
                                            ->label('Social Links')
                                            ->schema([
                                                Select::make('platform')
                                                    ->label('Platform')
                                                    ->options([
                                                        'linkedin' => 'LinkedIn',
                                                        'twitter' => 'Twitter / X',
                                                        'github' => 'GitHub',
                                                        'email' => 'Email',
                                                        'website' => 'Website',
                                                        'instagram' => 'Instagram',
                                                        'facebook' => 'Facebook',
                                                    ])
                                                    ->required(),

                                                TextInput::make('url')
                                                    ->label('URL')
                                                    ->required()
                                                    ->placeholder('https://linkedin.com/in/username')
                                                    ->maxLength(500),
                                            ])
                                            ->columns(2)
                                            ->defaultItems(0)
                                            ->maxItems(7)
                                            ->collapsible()
                                            ->itemLabel(fn (array $state): ?string => match ($state['platform'] ?? null) {
                                                'linkedin' => 'LinkedIn',
                                                'twitter' => 'Twitter / X',
                                                'github' => 'GitHub',
                                                'email' => 'Email',
                                                'website' => 'Website',
                                                'instagram' => 'Instagram',
                                                'facebook' => 'Facebook',
                                                default => 'Social Link',
                                            }),
                                    ])
                                    ->defaultItems(3)
                                    ->minItems(1)
                                    ->maxItems(20)
                                    ->collapsible()
                                    ->itemLabel(fn (array $state): ?string => $state['name'] ?? 'New Member')
                                    ->reorderableWithButtons(),
                            ]),

                        Tab::make('Layout')
                            ->icon('heroicon-m-squares-2x2')
                            ->schema([
                                Section::make('Grid Layout')
                                    ->schema([
                                        Select::make('columns')
                                            ->label('Columns')
                                            ->options([
                                                '2' => '2 Columns',
                                                '3' => '3 Columns',
                                                '4' => '4 Columns',
                                            ])
                                            ->default('3'),

                                        Select::make('card_style')
                                            ->label('Card Style')
                                            ->options(static::getTeamCardStyleOptions())
                                            ->default('card bg-base-200 shadow-lg'),

                                        Select::make('image_style')
                                            ->label('Photo Style')
                                            ->options([
                                                'rounded-full' => 'Circle',
                                                'rounded-xl' => 'Rounded Square',
                                                'rounded-none' => 'Square',
                                            ])
                                            ->default('rounded-full'),

                                        Select::make('text_alignment')
                                            ->label('Text Alignment')
                                            ->options(static::getTextAlignmentOptions())
                                            ->default('text-center'),
                                    ])
                                    ->columns(2),

                                Section::make('Appearance')
                                    ->schema([
                                        static::getContentWidthField(),

                                        Select::make('background')
                                            ->label('Background')
                                            ->options(static::getBackgroundOptions())
                                            ->default('bg-base-100'),

                                        Select::make('padding')
                                            ->label('Section Padding')
                                            ->options(static::getPaddingOptions())
                                            ->default('py-16'),
                                    ])
                                    ->columns(3),

                                Section::make('Content Display')
                                    ->schema([
                                        Toggle::make('show_bio')
                                            ->label('Show Bio')
                                            ->helperText('Display short biography text')
                                            ->default(true),

                                        Toggle::make('show_social')
                                            ->label('Show Social Links')
                                            ->default(true),

                                        Toggle::make('first_section')
                                            ->label('First Section (Remove Top Padding)')
                                            ->helperText('Overrides padding setting above')
                                            ->default(false),
                                    ])
                                    ->columns(3),
                            ]),

                        static::getAnimationTab(supportsStagger: true),
                    ]),

                static::getIdentifiersSection(),
            ])->slideOver();
    }

    public static function toPreviewHtml(array $config): string
    {
        $members = $config['members'] ?? self::getSampleMembers();

        return static::renderBlock(array_merge($config, [
            'members' => $members,
            'heading' => $config['heading'] ?? 'Meet Our Team',
            'subheading' => $config['subheading'] ?? 'The talented people behind our success',
        ]));
    }

    public static function toHtml(array $config, array $data): string
    {
        return static::renderBlock($config);
    }

    protected static function renderBlock(array $config): string
    {
        $widthConfig = static::resolveWidthClass($config);
        $animConfig = static::getAnimationConfig($config);

        return view('tallcms::cms.blocks.team', [
            'id' => static::getId(),
            'heading' => $config['heading'] ?? '',
            'subheading' => $config['subheading'] ?? '',
            'members' => $config['members'] ?? [],
            'columns' => $config['columns'] ?? '3',
            'card_style' => $config['card_style'] ?? 'card bg-base-200 shadow-lg',
            'image_style' => $config['image_style'] ?? 'rounded-full',
            'text_alignment' => $config['text_alignment'] ?? 'text-center',
            'contentWidthClass' => $widthConfig['class'],
            'contentPadding' => $widthConfig['padding'],
            'background' => $config['background'] ?? 'bg-base-100',
            'padding' => $config['padding'] ?? 'py-16',
            'show_bio' => $config['show_bio'] ?? true,
            'show_social' => $config['show_social'] ?? true,
            'first_section' => $config['first_section'] ?? false,
            'anchor_id' => static::getAnchorId($config, $config['heading'] ?? null),
            'css_classes' => static::getCssClasses($config),
            'animation_type' => $animConfig['animation_type'],
            'animation_duration' => $animConfig['animation_duration'],
            'animation_stagger' => $animConfig['animation_stagger'],
            'animation_stagger_delay' => $animConfig['animation_stagger_delay'],
        ])->render();
    }

    private static function getSampleMembers(): array
    {
        return [
            [
                'name' => 'Sarah Johnson',
                'role' => 'CEO & Founder',
                'bio' => 'Sarah has over 15 years of experience leading innovative tech companies.',
                'social_links' => [
                    ['platform' => 'linkedin', 'url' => '#'],
                    ['platform' => 'twitter', 'url' => '#'],
                ],
            ],
            [
                'name' => 'Michael Chen',
                'role' => 'CTO',
                'bio' => 'Michael leads our engineering team and oversees all technical decisions.',
                'social_links' => [
                    ['platform' => 'linkedin', 'url' => '#'],
                    ['platform' => 'github', 'url' => '#'],
                ],
            ],
            [
                'name' => 'Emily Rodriguez',
                'role' => 'Head of Design',
                'bio' => 'Emily brings creativity and user-centered thinking to everything we build.',
                'social_links' => [
                    ['platform' => 'linkedin', 'url' => '#'],
                    ['platform' => 'twitter', 'url' => '#'],
                ],
            ],
        ];
    }
}
