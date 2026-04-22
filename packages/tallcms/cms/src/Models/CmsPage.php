<?php

declare(strict_types=1);

namespace TallCms\Cms\Models;

use Filament\Forms\Components\RichEditor\Models\Concerns\InteractsWithRichContent;
use Filament\Forms\Components\RichEditor\Models\Contracts\HasRichContent;
use Filament\Forms\Components\RichEditor\RichContentRenderer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use TallCms\Cms\Casts\TranslatableArray;
use TallCms\Cms\Filament\Forms\Components\MediaLibraryFileAttachmentProvider;
use TallCms\Cms\Models\Concerns\HasPreviewTokens;
use TallCms\Cms\Models\Concerns\HasPublishingWorkflow;
use TallCms\Cms\Models\Concerns\HasRevisions;
use TallCms\Cms\Models\Concerns\HasSearchableContent;
use TallCms\Cms\Models\Concerns\HasTranslatableContent;
use TallCms\Cms\Services\CustomBlockDiscoveryService;

class CmsPage extends Model implements HasRichContent
{
    use HasFactory;
    use HasPreviewTokens;
    use HasPublishingWorkflow;
    use HasRevisions;
    use HasSearchableContent;
    use HasTranslatableContent;
    use InteractsWithRichContent;
    use SoftDeletes;

    protected $table = 'tallcms_pages';

    /**
     * Translatable attributes for Spatie Laravel Translatable.
     *
     * @var array<string>
     */
    public array $translatable = [
        'title',
        'slug',
        'content',
        'meta_title',
        'meta_description',
    ];

    protected $fillable = [
        'site_id',
        'title',
        'slug',
        'content',
        'search_content',
        'meta_title',
        'meta_description',
        'featured_image',
        'status',
        'is_homepage',
        'published_at',
        'parent_id',
        'sort_order',
        'show_breadcrumbs',
        'template',
        'sidebar_widgets',
        'content_width',
        'author_id',
        // Publishing workflow fields
        'approved_by',
        'approved_at',
        'rejection_reason',
        'submitted_by',
        'submitted_at',
        // Review metadata fields
        'last_reviewed_at',
        'reviewed_by',
        'expert_reviewer_name',
        'expert_reviewer_title',
        'expert_reviewer_url',
        'sources',
    ];

    protected $casts = [
        'content' => TranslatableArray::class,
        'sidebar_widgets' => 'array',
        'published_at' => 'datetime',
        'is_homepage' => 'boolean',
        'show_breadcrumbs' => 'boolean',
        'approved_at' => 'datetime',
        'submitted_at' => 'datetime',
        'last_reviewed_at' => 'datetime',
        'sources' => 'array',
    ];

    protected function setUpRichContent(): void
    {
        $this->registerRichContent('content')
            ->fileAttachmentProvider(MediaLibraryFileAttachmentProvider::make())
            ->customBlocks(CustomBlockDiscoveryService::getBlocksArray());
    }

    public function renderRichContentUnsafe(string $attribute): string
    {
        $content = $this->getAttribute($attribute);

        if (blank($content)) {
            return '';
        }

        $attr = $this->getRichContentAttribute($attribute);

        if (! $attr) {
            throw new \RuntimeException(
                "No rich content attribute registered for '{$attribute}'."
            );
        }

        return RichContentRenderer::make($content)
            ->customBlocks(CustomBlockDiscoveryService::getBlocksArray())
            ->fileAttachmentProvider($attr->getFileAttachmentProvider())
            ->toUnsafeHtml();
    }

    /**
     * Whether the site_id column exists on the pages table.
     * Cached per-process to avoid repeated schema checks.
     */
    protected static ?bool $hasSiteIdColumn = null;

    protected static function hasSiteIdColumn(): bool
    {
        return static::$hasSiteIdColumn ??= Schema::hasColumn('tallcms_pages', 'site_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($page) {
            if (empty($page->slug)) {
                $page->slug = $page->generateUniqueSlug($page->title);
            }

            // Ensure only one page can be marked as homepage (site-aware when multisite active)
            if ($page->is_homepage) {
                $query = static::withoutGlobalScopes()->where('is_homepage', true);
                if (static::hasSiteIdColumn() && $page->site_id) {
                    $query->where('site_id', $page->site_id);
                }
                $query->update(['is_homepage' => false]);
            }
        });

        static::updating(function ($page) {
            if ($page->isDirty('title') && empty($page->slug)) {
                $page->slug = $page->generateUniqueSlug($page->title);
            }

            // Ensure only one page can be marked as homepage (site-aware when multisite active)
            if ($page->is_homepage && $page->isDirty('is_homepage')) {
                $query = static::withoutGlobalScopes()->where('id', '!=', $page->id);
                if (static::hasSiteIdColumn() && $page->site_id) {
                    $query->where('site_id', $page->site_id);
                }
                $query->update(['is_homepage' => false]);
            }
        });

        static::saved(function ($page) {
            MediaLibraryFileAttachmentProvider::syncAltTextFromContent($page->content);
        });
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(CmsPage::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(CmsPage::class, 'parent_id')->orderBy('sort_order');
    }

    /**
     * Determine if breadcrumbs should be displayed for this page.
     */
    public function shouldShowBreadcrumbs(): bool
    {
        if ($this->is_homepage) {
            return false;
        }

        return $this->show_breadcrumbs ?? true;
    }

    /**
     * Get the breadcrumb trail for this page.
     * Walks up the full ancestor chain for deep hierarchies.
     */
    public function getBreadcrumbTrail(): array
    {
        $breadcrumbs = [
            ['name' => __('Home'), 'url' => url(tallcms_localized_url('/'))],
        ];

        // Collect ancestors by walking up the parent chain (with loop guard)
        $ancestors = [];
        $current = $this->parent;
        $visited = [];
        while ($current && ! in_array($current->id, $visited) && count($visited) < 10) {
            $visited[] = $current->id;
            $ancestors[] = $current;
            $current = $current->parent;
        }

        // Add ancestors in reverse order (root → leaf)
        foreach (array_reverse($ancestors) as $ancestor) {
            $breadcrumbs[] = [
                'name' => $ancestor->title,
                'url' => url(tallcms_localized_url($ancestor->slug)),
            ];
        }

        // Add current page (last item, canonical URL without query params)
        $breadcrumbs[] = [
            'name' => $this->title,
            'url' => url(tallcms_localized_url($this->slug)),
        ];

        return $breadcrumbs;
    }

    public function author(): BelongsTo
    {
        $userModel = config('tallcms.plugin_mode.user_model', \App\Models\User::class);

        return $this->belongsTo($userModel, 'author_id');
    }

    public function reviewer(): BelongsTo
    {
        $userModel = config('tallcms.plugin_mode.user_model', \App\Models\User::class);

        return $this->belongsTo($userModel, 'reviewed_by');
    }

    public function scopeWithSlug($query, string $slug)
    {
        // Slug is stored as JSON (translatable), so use JSON query
        // This works regardless of whether i18n is enabled
        $locale = config('app.locale', 'en');
        $driver = $query->getConnection()->getDriverName();

        return match ($driver) {
            'sqlite' => $query->whereRaw("JSON_EXTRACT(slug, '$.{$locale}') = ?", [$slug]),
            'pgsql' => $query->whereRaw('slug::jsonb ->> ? = ?', [$locale, $slug]),
            default => $query->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(slug, '$.\"".$locale."\"')) = ?", [$slug]),
        };
    }

    public function getRouteKeyName(): string
    {
        // Use ID for route binding since slug is now stored as JSON (translatable)
        return 'id';
    }

    public function scopeHomepage($query)
    {
        return $query->where('is_homepage', true);
    }

    public static function getHomepage(): ?self
    {
        return static::homepage()->published()->first();
    }

    /**
     * Generate a unique slug from title.
     *
     * When i18n is enabled, this delegates to the trait's locale-aware version.
     * When i18n is disabled, uses simple slug generation.
     *
     * @param  string  $title  The title to generate slug from
     * @param  string|null  $locale  The locale (only used when i18n is enabled)
     */
    public function generateUniqueSlug(string $title, ?string $locale = null): string
    {
        // When i18n is enabled, use the trait's locale-aware version
        if (tallcms_i18n_enabled()) {
            $locale = $locale ?? app()->getLocale();
            $baseSlug = Str::slug($title);
            $slug = $baseSlug;
            $counter = 1;

            // Check reserved slugs (locale codes)
            $reserved = app(\TallCms\Cms\Services\LocaleRegistry::class)->getReservedSlugs();
            if (in_array($slug, $reserved)) {
                $slug = $baseSlug.'-page';
            }

            while ($this->localizedSlugExists($slug, $locale)) {
                $slug = $baseSlug.'-'.$counter;
                $counter++;
            }

            return $slug;
        }

        // Non-i18n mode: simple slug generation
        $baseSlug = Str::slug($title);
        $slug = $baseSlug;
        $counter = 1;

        while ($this->slugExists($slug)) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Check if slug already exists (excluding current record).
     * Used when i18n is disabled, but data is still stored as JSON.
     */
    protected function slugExists(string $slug): bool
    {
        $locale = config('app.locale', 'en');
        $query = static::query();
        $driver = $query->getConnection()->getDriverName();

        match ($driver) {
            'sqlite' => $query->whereRaw("JSON_EXTRACT(slug, '$.{$locale}') = ?", [$slug]),
            'pgsql' => $query->whereRaw('slug::jsonb ->> ? = ?', [$locale, $slug]),
            default => $query->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(slug, '$.\"".$locale."\"')) = ?", [$slug]),
        };

        if ($this->exists) {
            $query->where('id', '!=', $this->id);
        }

        return $query->exists();
    }

    /**
     * Extract Posts block configuration from the page content.
     * Returns the config array of the first Posts block found, or empty array.
     * Handles both Tiptap JSON format and HTML format with data attributes.
     */
    public function getPostsBlockConfig(): array
    {
        if (empty($this->content)) {
            return [];
        }

        $content = $this->content;

        if (is_string($content)) {
            // Try JSON format first (Tiptap)
            $decoded = json_decode($content, true);
            if (is_array($decoded)) {
                return static::extractPostsBlockConfigFromArray($decoded);
            }

            // Fall back to HTML format with data attributes
            return static::extractPostsBlockConfigFromHtml($content);
        }

        if (is_array($content)) {
            return static::extractPostsBlockConfigFromArray($content);
        }

        return [];
    }

    /**
     * Get all unique category IDs referenced by Posts blocks on this page.
     */
    public function getPostsBlockCategoryIds(): array
    {
        if (empty($this->content)) {
            return [];
        }

        $content = $this->content;
        $configs = [];

        if (is_string($content)) {
            $decoded = json_decode($content, true);
            if (is_array($decoded)) {
                static::collectAllPostsBlockConfigs($decoded, $configs);
            } else {
                // HTML format: find all Posts block configs
                preg_match_all('/data-(?:type=["\']customBlock["\'][^>]*)?data-id=["\']posts["\'][^>]*data-config=["\']([^"\']+)["\']/', $content, $matches);
                if (empty($matches[1])) {
                    preg_match_all('/data-config=["\']([^"\']+)["\'][^>]*data-id=["\']posts["\']/', $content, $matches);
                }
                foreach ($matches[1] ?? [] as $configJson) {
                    $config = json_decode(html_entity_decode($configJson, ENT_QUOTES, 'UTF-8'), true);
                    if (is_array($config)) {
                        $configs[] = $config;
                    }
                }
            }
        } elseif (is_array($content)) {
            static::collectAllPostsBlockConfigs($content, $configs);
        }

        $categoryIds = [];
        foreach ($configs as $config) {
            foreach ($config['categories'] ?? [] as $id) {
                $categoryIds[] = (int) $id;
            }
        }

        return array_unique($categoryIds);
    }

    /**
     * Recursively collect all Posts block configs from Tiptap JSON content.
     */
    protected static function collectAllPostsBlockConfigs(array $content, array &$configs): void
    {
        if (isset($content['type']) && $content['type'] === 'customBlock' &&
            isset($content['attrs']['id']) && $content['attrs']['id'] === 'posts') {
            $configs[] = $content['attrs']['config'] ?? [];

            return;
        }

        foreach ($content as $value) {
            if (is_array($value)) {
                static::collectAllPostsBlockConfigs($value, $configs);
            }
        }
    }

    /**
     * Extract posts block config from HTML content with data attributes.
     */
    public static function extractPostsBlockConfigFromHtml(string $html): array
    {
        if (preg_match('/data-type=["\']customBlock["\'][^>]*data-id=["\']posts["\'][^>]*data-config=["\']([^"\']+)["\']/', $html, $matches) ||
            preg_match('/data-id=["\']posts["\'][^>]*data-type=["\']customBlock["\'][^>]*data-config=["\']([^"\']+)["\']/', $html, $matches) ||
            preg_match('/data-config=["\']([^"\']+)["\'][^>]*data-type=["\']customBlock["\'][^>]*data-id=["\']posts["\']/', $html, $matches) ||
            preg_match('/data-config=["\']([^"\']+)["\'][^>]*data-id=["\']posts["\']/', $html, $matches)) {
            $configJson = html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8');
            $config = json_decode($configJson, true);

            return is_array($config) ? $config : [];
        }

        return [];
    }

    /**
     * Recursively extract posts block config from Tiptap JSON content.
     */
    public static function extractPostsBlockConfigFromArray(array $content): array
    {
        if (isset($content['type']) && $content['type'] === 'customBlock' &&
            isset($content['attrs']['id']) && $content['attrs']['id'] === 'posts') {
            return $content['attrs']['config'] ?? [];
        }

        foreach ($content as $value) {
            if (is_array($value)) {
                $config = static::extractPostsBlockConfigFromArray($value);
                if (! empty($config)) {
                    return $config;
                }
            }
        }

        return [];
    }

    /**
     * Get the CSS class for the page's content width setting.
     */
    public function getContentWidthClass(): string
    {
        return match ($this->content_width ?? 'standard') {
            'narrow' => 'max-w-2xl',
            'standard' => 'max-w-6xl',
            'wide' => 'max-w-7xl',
            default => 'max-w-6xl',
        };
    }
}
