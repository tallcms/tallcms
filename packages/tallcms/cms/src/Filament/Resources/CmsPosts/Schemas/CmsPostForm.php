<?php

namespace TallCms\Cms\Filament\Resources\CmsPosts\Schemas;

use TallCms\Cms\Enums\ContentStatus;
use TallCms\Cms\Livewire\RevisionHistory;
use TallCms\Cms\Models\CmsCategory;
use TallCms\Cms\Models\CmsPost;
use TallCms\Cms\Services\CustomBlockDiscoveryService;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CmsPostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Post Management')
                    ->tabs([
                        Tabs\Tab::make('Content')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Section::make()
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('title')
                                            ->required(fn () => ! tallcms_i18n_enabled())
                                            ->maxLength(255)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(fn (string $state, callable $set) => $set('slug', Str::slug($state))
                                            )
                                            ->columnSpan(1),

                                        TextInput::make('slug')
                                            ->required(fn () => ! tallcms_i18n_enabled())
                                            ->maxLength(255)
                                            ->when(! tallcms_i18n_enabled(), fn ($field) => $field->unique(CmsPost::class, 'slug', ignoreRecord: true))
                                            ->rules(['alpha_dash'])
                                            ->helperText('Used in the URL')
                                            ->columnSpan(1),
                                    ]),

                                Textarea::make('excerpt')
                                    ->label('Excerpt')
                                    ->maxLength(500)
                                    ->rows(3)
                                    ->helperText('Brief description shown in post listings')
                                    ->columnSpanFull(),

                                RichEditor::make('content')
                                    ->columnSpanFull()
                                    ->fileAttachmentsDirectory('cms/posts/attachments')
                                    ->activePanel('customBlocks')
                                    ->customBlocks(CustomBlockDiscoveryService::getBlocksArray())
                                    ->extraInputAttributes([
                                        'style' => 'min-height: 40rem;',
                                    ])
                                     ->floatingToolbars([
                                        'paragraph' => [
                                            'bold', 'italic', 'underline', 'strike', 'subscript', 'superscript',
                                        ],
                                        'heading' => [
                                            'h1', 'h2', 'h3',
                                        ],
                                        'table' => [
                                            'tableAddColumnBefore', 'tableAddColumnAfter', 'tableDeleteColumn',
                                            'tableAddRowBefore', 'tableAddRowAfter', 'tableDeleteRow',
                                            'tableMergeCells', 'tableSplitCell',
                                            'tableToggleHeaderRow', 'tableToggleHeaderCell',
                                            'tableDelete',
                                        ],
                                    ])
                                    ->helperText('Create rich post content using the editor toolbar.'),
                            ]),

                        Tabs\Tab::make('Settings')
                            ->icon('heroicon-o-cog-6-tooth')
                            ->schema([
                                Section::make('Post Settings')
                                    ->columns(2)
                                    ->schema([
                                        Select::make('status')
                                            ->options(function () {
                                                // Authors can only set draft/pending, approvers can set all
                                                if (auth()->user()?->can('Approve:CmsPost')) {
                                                    return ContentStatus::editorOptions();
                                                }

                                                return ContentStatus::authorOptions();
                                            })
                                            ->required()
                                            ->default(ContentStatus::Draft->value)
                                            ->disabled(function (?CmsPost $record) {
                                                // Disable status change when pending and user can't approve
                                                if ($record?->isPending() && ! auth()->user()?->can('Approve:CmsPost')) {
                                                    return true;
                                                }

                                                return false;
                                            })
                                            ->helperText(function (?CmsPost $record) {
                                                if ($record?->wasRejected()) {
                                                    return 'This content was rejected. Reason: '.$record->getRejectionReason();
                                                }
                                                if ($record?->isPending()) {
                                                    return 'This content is pending review.';
                                                }

                                                return null;
                                            }),

                                        DateTimePicker::make('published_at')
                                            ->label('Publish Date')
                                            ->nullable()
                                            ->helperText('Leave empty to publish immediately when approved, or set a future date to schedule.')
                                            ->visible(fn () => auth()->user()?->can('Approve:CmsPost')),

                                        Select::make('author_id')
                                            ->label('Author')
                                            ->options(function () {
                                                $userModel = config('tallcms.plugin_mode.user_model', 'App\\Models\\User');

                                                return $userModel::query()->pluck('name', 'id');
                                            })
                                            ->default(auth()->id())
                                            ->required(),

                                        Toggle::make('is_featured')
                                            ->label('Featured Post')
                                            ->helperText('Featured posts appear prominently'),
                                    ]),

                                Section::make('Categories')
                                    ->schema([
                                        Select::make('categories')
                                            ->multiple()
                                            ->relationship('categories', 'name')
                                            ->options(CmsCategory::query()->pluck('name', 'id'))
                                            ->searchable()
                                            ->preload()
                                            ->createOptionForm([
                                                TextInput::make('name')
                                                    ->required()
                                                    ->live(onBlur: true)
                                                    ->afterStateUpdated(fn (string $state, callable $set) => $set('slug', Str::slug($state))
                                                    ),
                                                TextInput::make('slug')
                                                    ->required()
                                                    ->unique(CmsCategory::class, 'slug'),
                                                Textarea::make('description')
                                                    ->rows(2),
                                            ])
                                            ->helperText('Select existing categories or create new ones for this post'),
                                    ]),
                            ]),

                        Tabs\Tab::make('SEO')
                            ->icon('heroicon-o-magnifying-glass')
                            ->schema([
                                Section::make('Search Engine Optimization')
                                    ->description('Optimize your post for search engines and social media sharing')
                                    ->schema([
                                        TextInput::make('meta_title')
                                            ->label('Meta Title')
                                            ->maxLength(60)
                                            ->helperText('Recommended: 50-60 characters. If empty, post title will be used.'),

                                        Textarea::make('meta_description')
                                            ->label('Meta Description')
                                            ->maxLength(160)
                                            ->rows(3)
                                            ->helperText('Recommended: 150-160 characters. If empty, excerpt will be used.'),

                                        FileUpload::make('featured_image')
                                            ->label('Featured Image')
                                            ->image()
                                            ->directory('cms/posts/featured-images')
                                            ->disk(\cms_media_disk())
                                            ->visibility(\cms_media_visibility())
                                            ->imageEditor()
                                            ->imageEditorAspectRatios([
                                                '16:9',
                                                '4:3',
                                                '1:1',
                                                '1.91:1', // Facebook recommended
                                                '2:1',    // Twitter header
                                            ])
                                            ->helperText('Used for social media sharing and post headers. Recommended: 1200x630px for best compatibility.'),
                                    ]),
                            ]),

                        Tabs\Tab::make('Revisions')
                            ->icon('heroicon-o-clock')
                            ->visible(fn (?CmsPost $record) => $record !== null && auth()->user()?->can('ViewRevisions:CmsPost'))
                            ->schema([
                                Livewire::make(RevisionHistory::class)
                                    ->lazy()
                                    ->data(fn (?CmsPost $record) => ['record' => $record]),
                            ]),
                    ])
                    ->persistTabInQueryString()
                    ->columnSpanFull(),
            ]);
    }
}
