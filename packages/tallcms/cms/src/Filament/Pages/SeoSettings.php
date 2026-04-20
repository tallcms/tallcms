<?php

namespace TallCms\Cms\Filament\Pages;

use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use TallCms\Cms\Models\SiteSetting;
use TallCms\Cms\Services\SitemapService;

class SeoSettings extends Page implements HasForms
{
    use HasPageShield, InteractsWithForms;

    protected string $view = 'tallcms::filament.pages.seo-settings';

    protected static ?string $navigationLabel = 'SEO Settings';

    protected static ?string $title = 'SEO Settings';

    public ?array $data = [];

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-magnifying-glass-circle';
    }

    public static function getNavigationGroup(): ?string
    {
        return config('tallcms.navigation.groups.configuration', 'Configuration');
    }

    public static function getNavigationSort(): ?int
    {
        return 41;
    }

    public function mount(): void
    {
        $this->form->fill([
            // RSS settings
            'seo_rss_enabled' => SiteSetting::getGlobal('seo_rss_enabled', true),
            'seo_rss_limit' => SiteSetting::getGlobal('seo_rss_limit', 20),
            'seo_rss_full_content' => SiteSetting::getGlobal('seo_rss_full_content', false),

            // Sitemap settings
            'seo_sitemap_enabled' => SiteSetting::getGlobal('seo_sitemap_enabled', true),

            // robots.txt settings
            'seo_robots_txt' => SiteSetting::getGlobal('seo_robots_txt', $this->getDefaultRobots()),
            'seo_robots_append_sitemap' => SiteSetting::getGlobal('seo_robots_append_sitemap', true),

            // Default OG image
            'seo_default_og_image' => SiteSetting::getGlobal('seo_default_og_image'),

            // llms.txt
            'seo_llms_txt_enabled' => SiteSetting::getGlobal('seo_llms_txt_enabled', false),
            'seo_llms_txt_preamble' => SiteSetting::getGlobal('seo_llms_txt_preamble', ''),
            'seo_llms_txt_include_pages' => SiteSetting::getGlobal('seo_llms_txt_include_pages', true),
            'seo_llms_txt_include_posts' => SiteSetting::getGlobal('seo_llms_txt_include_posts', true),
            'seo_llms_txt_post_limit' => SiteSetting::getGlobal('seo_llms_txt_post_limit', '0'),
        ]);
    }

    protected function getFormSchema(): array
    {
        return [
            Section::make('RSS Feed')
                ->description('Configure RSS feed settings')
                ->schema([
                    Toggle::make('seo_rss_enabled')
                        ->label('Enable RSS Feed')
                        ->helperText('Allow visitors to subscribe to your posts via RSS')
                        ->live(),

                    Select::make('seo_rss_limit')
                        ->label('Posts in Feed')
                        ->options([
                            '10' => '10 posts',
                            '20' => '20 posts',
                            '30' => '30 posts',
                            '50' => '50 posts',
                        ])
                        ->default('20')
                        ->visible(fn ($get) => $get('seo_rss_enabled'))
                        ->helperText('Number of most recent posts to include'),

                    Toggle::make('seo_rss_full_content')
                        ->label('Include Full Content')
                        ->helperText('Include complete post content instead of just excerpt')
                        ->visible(fn ($get) => $get('seo_rss_enabled')),
                ])
                ->columns(3),

            Section::make('XML Sitemap')
                ->description('Configure sitemap generation for search engines')
                ->schema([
                    Toggle::make('seo_sitemap_enabled')
                        ->label('Enable XML Sitemap')
                        ->helperText('Generate sitemap.xml for search engine crawlers')
                        ->columnSpanFull(),
                ]),

            Section::make('robots.txt')
                ->description('Control how search engines crawl your site')
                ->schema([
                    Textarea::make('seo_robots_txt')
                        ->label('robots.txt Content')
                        ->rows(8)
                        ->placeholder($this->getDefaultRobots())
                        ->helperText('Customize your robots.txt file. Leave empty for default.')
                        ->columnSpanFull(),

                    Toggle::make('seo_robots_append_sitemap')
                        ->label('Auto-append Sitemap URL')
                        ->helperText('Automatically add Sitemap: directive to robots.txt')
                        ->columnSpanFull(),
                ]),

            Section::make('Default Open Graph Image')
                ->description('Fallback image for social media sharing')
                ->schema([
                    FileUpload::make('seo_default_og_image')
                        ->label('Default OG Image')
                        ->image()
                        ->directory('site-assets')
                        ->disk(\cms_media_disk())
                        ->visibility(\cms_media_visibility())
                        ->helperText('Used when pages/posts don\'t have a featured image. Recommended: 1200x630 pixels.')
                        ->nullable()
                        ->columnSpanFull(),
                ]),

            Section::make('llms.txt')
                ->description('Machine-readable content index for AI systems. The file is auto-generated from your published content — these settings control what is included.')
                ->schema([
                    Toggle::make('seo_llms_txt_enabled')
                        ->label('Enable llms.txt')
                        ->helperText('Publish a /llms.txt file for AI consumption')
                        ->live()
                        ->columnSpanFull(),

                    Textarea::make('seo_llms_txt_preamble')
                        ->label('Preamble')
                        ->rows(3)
                        ->maxLength(500)
                        ->placeholder('e.g., TallCMS is a Laravel-based CMS built on the TALL stack...')
                        ->helperText('A short intro paragraph shown at the top. Keep it factual and concise.')
                        ->visible(fn ($get) => $get('seo_llms_txt_enabled'))
                        ->columnSpanFull(),

                    Toggle::make('seo_llms_txt_include_pages')
                        ->label('Include Pages')
                        ->helperText('List published pages')
                        ->default(true)
                        ->visible(fn ($get) => $get('seo_llms_txt_enabled')),

                    Toggle::make('seo_llms_txt_include_posts')
                        ->label('Include Posts')
                        ->helperText('List published posts grouped by category')
                        ->default(true)
                        ->visible(fn ($get) => $get('seo_llms_txt_enabled')),

                    Select::make('seo_llms_txt_post_limit')
                        ->label('Post Limit')
                        ->options([
                            '0' => 'All posts',
                            '10' => '10 most recent',
                            '25' => '25 most recent',
                            '50' => '50 most recent',
                            '100' => '100 most recent',
                        ])
                        ->default('0')
                        ->helperText('Limit total posts to keep the file focused')
                        ->visible(fn ($get) => $get('seo_llms_txt_enabled') && $get('seo_llms_txt_include_posts')),
                ])
                ->columns(3),
        ];
    }

    protected function getFormStatePath(): string
    {
        return 'data';
    }

    protected function getDefaultRobots(): string
    {
        return "User-agent: *\nAllow: /";
    }

    public function save(): void
    {
        $data = $this->form->getState();

        foreach ($data as $key => $value) {
            if (str_starts_with($key, 'seo_')) {
                $type = match ($key) {
                    'seo_default_og_image' => 'file',
                    'seo_rss_enabled', 'seo_rss_full_content', 'seo_sitemap_enabled', 'seo_robots_append_sitemap',
                    'seo_llms_txt_enabled', 'seo_llms_txt_include_pages', 'seo_llms_txt_include_posts' => 'boolean',
                    'seo_rss_limit', 'seo_llms_txt_post_limit' => 'integer',
                    default => 'text',
                };

                SiteSetting::setGlobal($key, $value ?? ($type === 'boolean' ? false : null), $type, 'seo');
            }
        }

        // Clear caches
        SiteSetting::clearCache();
        SitemapService::clearCache();

        Notification::make()
            ->title('SEO settings saved successfully!')
            ->success()
            ->send();
    }

    public function clearSitemapCache(): void
    {
        SitemapService::clearCache();

        Notification::make()
            ->title('Sitemap cache cleared')
            ->body('The sitemap will be regenerated on the next request.')
            ->success()
            ->send();
    }
}
