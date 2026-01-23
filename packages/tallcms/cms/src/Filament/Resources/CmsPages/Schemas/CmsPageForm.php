<?php

namespace TallCms\Cms\Filament\Resources\CmsPages\Schemas;

use TallCms\Cms\Enums\ContentStatus;
use TallCms\Cms\Livewire\RevisionHistory;
use TallCms\Cms\Models\CmsPage;
use TallCms\Cms\Rules\UniqueTranslatableSlug;
use TallCms\Cms\Services\CustomBlockDiscoveryService;
use TallCms\Cms\Services\LocaleRegistry;
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

class CmsPageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Page Management')
                    ->tabs([
                        Tabs\Tab::make('Content')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Section::make()
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('title')
                                            ->required(function ($livewire) {
                                                if (! tallcms_i18n_enabled()) {
                                                    return true;
                                                }
                                                // Require title for default locale when i18n enabled
                                                $activeLocale = $livewire->activeLocale ?? app()->getLocale();
                                                $defaultLocale = app(LocaleRegistry::class)->getDefaultLocale();

                                                return $activeLocale === $defaultLocale;
                                            })
                                            ->maxLength(255)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(fn (string $state, callable $set) => $set('slug', Str::slug($state))
                                            )
                                            ->columnSpan(1),

                                        TextInput::make('slug')
                                            ->required(function ($livewire) {
                                                if (! tallcms_i18n_enabled()) {
                                                    return true;
                                                }
                                                // Require slug for default locale when i18n enabled
                                                $activeLocale = $livewire->activeLocale ?? app()->getLocale();
                                                $defaultLocale = app(LocaleRegistry::class)->getDefaultLocale();

                                                return $activeLocale === $defaultLocale;
                                            })
                                            ->maxLength(255)
                                            ->rules(function (?CmsPage $record, $livewire) {
                                                $rules = ['alpha_dash'];

                                                if (tallcms_i18n_enabled()) {
                                                    // Block locale codes as slugs
                                                    $reserved = app(LocaleRegistry::class)->getReservedSlugs();
                                                    $rules[] = 'not_in:'.implode(',', $reserved);

                                                    // Unique per locale
                                                    $activeLocale = $livewire->activeLocale ?? app()->getLocale();
                                                    $rules[] = new UniqueTranslatableSlug(
                                                        table: 'tallcms_pages',
                                                        column: 'slug',
                                                        locale: $activeLocale,
                                                        ignoreId: $record?->id
                                                    );
                                                } else {
                                                    // Traditional unique constraint
                                                    $rules[] = 'unique:tallcms_pages,slug'.($record ? ','.$record->id : '');
                                                }

                                                return $rules;
                                            })
                                            ->validationMessages([
                                                'not_in' => 'This slug is reserved (matches a language code).',
                                            ])
                                            ->helperText('Used in the URL. Keep it simple and SEO-friendly.')
                                            ->columnSpan(1),
                                    ]),
                                RichEditor::make('content')
                                    ->columnSpanFull()
                                    ->fileAttachmentsDirectory('cms/attachments')
                                    ->toolbarButtons([
                                        ['grid', 'gridDelete', 'table', 'attachFiles', 'customBlocks', 'mergeTags'], // The `customBlocks` and `mergeTags` tools are also added here if those features are used.
                                    ])
                                    ->activePanel('customBlocks')
                                    ->mergeTags([
                                        'site_name',
                                        'current_year',
                                        'page_title',
                                    ])
                                    ->customBlocks(CustomBlockDiscoveryService::getBlocksArray())
                                    ->extraInputAttributes([
                                        'style' => 'min-height: 40rem;',
                                    ])
                                    ->helperText('Create rich page content with custom blocks, merge tags, and advanced formatting. Use merge tags to insert dynamic content like {{site_name}} or {{current_year}}.'),
                            ]),

                        Tabs\Tab::make('Settings')
                            ->icon('heroicon-o-cog-6-tooth')
                            ->schema([
                                Section::make('Page Settings')
                                    ->columns(2)
                                    ->schema([
                                        Select::make('status')
                                            ->options(function () {
                                                // Authors can only set draft/pending, approvers can set all
                                                if (auth()->user()?->can('Approve:CmsPage')) {
                                                    return ContentStatus::editorOptions();
                                                }

                                                return ContentStatus::authorOptions();
                                            })
                                            ->required()
                                            ->default(ContentStatus::Draft->value)
                                            ->disabled(function (?CmsPage $record) {
                                                // Disable status change when pending and user can't approve
                                                if ($record?->isPending() && ! auth()->user()?->can('Approve:CmsPage')) {
                                                    return true;
                                                }

                                                return false;
                                            })
                                            ->helperText(function (?CmsPage $record) {
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
                                            ->visible(fn () => auth()->user()?->can('Approve:CmsPage')),

                                        Select::make('author_id')
                                            ->label('Author')
                                            ->options(function () {
                                                $userModel = config('tallcms.plugin_mode.user_model', 'App\\Models\\User');

                                                return $userModel::query()->pluck('name', 'id');
                                            })
                                            ->default(auth()->id())
                                            ->nullable()
                                            ->searchable(),

                                        Toggle::make('is_homepage')
                                            ->label('Set as Homepage')
                                            ->helperText('Only one page can be set as homepage. This will override any existing homepage setting.')
                                            ->columnSpan(2),

                                        Select::make('parent_id')
                                            ->label('Parent Page')
                                            ->options(CmsPage::query()
                                                ->whereNull('parent_id')
                                                ->pluck('title', 'id'))
                                            ->searchable()
                                            ->nullable()
                                            ->columnSpan(1),

                                        TextInput::make('sort_order')
                                            ->numeric()
                                            ->default(0)
                                            ->columnSpan(1),

                                        TextInput::make('template')
                                            ->helperText('Optional: Custom blade template')
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        Tabs\Tab::make('SEO')
                            ->icon('heroicon-o-magnifying-glass')
                            ->schema([
                                Section::make('Search Engine Optimization')
                                    ->description('Optimize your page for search engines and social media sharing')
                                    ->schema([
                                        TextInput::make('meta_title')
                                            ->label('Meta Title')
                                            ->maxLength(60)
                                            ->helperText('Recommended: 50-60 characters. If empty, page title will be used.'),

                                        Textarea::make('meta_description')
                                            ->label('Meta Description')
                                            ->maxLength(160)
                                            ->rows(3)
                                            ->helperText('Recommended: 150-160 characters. Brief description for search results.'),

                                        FileUpload::make('featured_image')
                                            ->label('Featured Image')
                                            ->image()
                                            ->directory('cms/pages/featured-images')
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
                                            ->helperText('Used for social media sharing and page headers. Recommended: 1200x630px for best compatibility.'),
                                    ]),
                            ]),

                        Tabs\Tab::make('Revisions')
                            ->icon('heroicon-o-clock')
                            ->visible(fn (?CmsPage $record) => $record !== null && auth()->user()?->can('ViewRevisions:CmsPage'))
                            ->schema([
                                Livewire::make(RevisionHistory::class)
                                    ->lazy()
                                    ->data(fn (?CmsPage $record) => ['record' => $record]),
                            ]),
                    ])
                    ->persistTabInQueryString()
                    ->columnSpanFull(),
            ]);
    }
}
