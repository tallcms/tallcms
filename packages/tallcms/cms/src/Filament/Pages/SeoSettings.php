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
        return config('tallcms.filament.navigation_group') ?? 'Settings';
    }

    public static function getNavigationSort(): ?int
    {
        return (config('tallcms.filament.navigation_sort') ?? 99) + 1;
    }

    public function mount(): void
    {
        $this->form->fill([
            // RSS settings
            'seo_rss_enabled' => SiteSetting::get('seo_rss_enabled', true),
            'seo_rss_limit' => SiteSetting::get('seo_rss_limit', 20),
            'seo_rss_full_content' => SiteSetting::get('seo_rss_full_content', false),

            // Sitemap settings
            'seo_sitemap_enabled' => SiteSetting::get('seo_sitemap_enabled', true),

            // robots.txt settings
            'seo_robots_txt' => SiteSetting::get('seo_robots_txt', $this->getDefaultRobots()),
            'seo_robots_append_sitemap' => SiteSetting::get('seo_robots_append_sitemap', true),

            // Default OG image
            'seo_default_og_image' => SiteSetting::get('seo_default_og_image'),
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
                    'seo_rss_enabled', 'seo_rss_full_content', 'seo_sitemap_enabled', 'seo_robots_append_sitemap' => 'boolean',
                    'seo_rss_limit' => 'integer',
                    default => 'text',
                };

                SiteSetting::set($key, $value ?? ($type === 'boolean' ? false : null), $type, 'seo');
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
