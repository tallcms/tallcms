<?php

namespace App\Filament\Resources\CmsPosts\Schemas;

use App\Models\CmsCategory;
use App\Models\CmsPost;
use App\Models\User;
use App\Services\CustomBlockDiscoveryService;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CmsPostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Article Management')
                    ->tabs([
                        Tabs\Tab::make('Content')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Section::make()
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('title')
                                            ->required()
                                            ->maxLength(255)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(fn (string $state, callable $set) => 
                                                $set('slug', Str::slug($state))
                                            )
                                            ->columnSpan(1),
                                            
                                        TextInput::make('slug')
                                            ->required()
                                            ->maxLength(255)
                                            ->unique(CmsPost::class, 'slug', ignoreRecord: true)
                                            ->rules(['alpha_dash'])
                                            ->helperText('Used in the URL')
                                            ->columnSpan(1),
                                    ]),
                                    
                                Textarea::make('excerpt')
                                    ->label('Excerpt')
                                    ->maxLength(500)
                                    ->rows(3)
                                    ->helperText('Brief description shown in article listings')
                                    ->columnSpanFull(),
                                    
                                RichEditor::make('content')
                                    ->columnSpanFull()
                                    ->fileAttachmentsDirectory('cms/posts/attachments')
                                    ->mergeTags([
                                        'site_name',
                                        'current_year',
                                        'post_title',
                                        'post_author',
                                    ])
                                    ->customBlocks(CustomBlockDiscoveryService::getBlocksArray())
                                    ->extraInputAttributes([
                                        'style' => 'min-height: 40rem;',
                                    ])
                                    ->helperText('Create rich article content with custom blocks, merge tags, and advanced formatting. Use merge tags to insert dynamic content like {{post_author}} or {{post_title}}.'),
                            ]),
                            
                        Tabs\Tab::make('Settings')
                            ->icon('heroicon-o-cog-6-tooth')
                            ->schema([
                                Section::make('Article Settings')
                                    ->columns(2)
                                    ->schema([
                                        Select::make('status')
                                            ->options([
                                                'draft' => 'Draft',
                                                'published' => 'Published',
                                            ])
                                            ->required()
                                            ->default('draft'),
                                            
                                        DateTimePicker::make('published_at')
                                            ->label('Publish Date')
                                            ->nullable()
                                            ->default(now()),
                                            
                                        Select::make('author_id')
                                            ->label('Author')
                                            ->options(User::query()->pluck('name', 'id'))
                                            ->default(auth()->id())
                                            ->required(),
                                            
                                        Toggle::make('is_featured')
                                            ->label('Featured Article')
                                            ->helperText('Featured articles appear prominently'),
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
                                                    ->afterStateUpdated(fn (string $state, callable $set) => 
                                                        $set('slug', Str::slug($state))
                                                    ),
                                                TextInput::make('slug')
                                                    ->required()
                                                    ->unique(CmsCategory::class, 'slug'),
                                                Textarea::make('description')
                                                    ->rows(2),
                                            ])
                                            ->helperText('Select existing categories or create new ones for this article'),
                                    ]),
                            ]),
                            
                        Tabs\Tab::make('SEO')
                            ->icon('heroicon-o-magnifying-glass')
                            ->schema([
                                Section::make('Search Engine Optimization')
                                    ->description('Optimize your article for search engines and social media sharing')
                                    ->schema([
                                        TextInput::make('meta_title')
                                            ->label('Meta Title')
                                            ->maxLength(60)
                                            ->helperText('Recommended: 50-60 characters. If empty, article title will be used.'),
                                            
                                        Textarea::make('meta_description')
                                            ->label('Meta Description')
                                            ->maxLength(160)
                                            ->rows(3)
                                            ->helperText('Recommended: 150-160 characters. If empty, excerpt will be used.'),
                                            
                                        FileUpload::make('featured_image')
                                            ->label('Featured Image')
                                            ->image()
                                            ->directory('cms/posts/featured-images')
                                            ->disk(cms_media_disk())
                                            ->visibility(cms_media_visibility())
                                            ->imageEditor()
                                            ->imageEditorAspectRatios([
                                                '16:9',
                                                '4:3', 
                                                '1:1',
                                                '1.91:1', // Facebook recommended
                                                '2:1',    // Twitter header
                                            ])
                                            ->helperText('Used for social media sharing and article headers. Recommended: 1200x630px for best compatibility.'),
                                    ]),
                            ]),
                    ])
                    ->persistTabInQueryString()
                    ->columnSpanFull(),
            ]);
    }
}