<?php

declare(strict_types=1);

namespace TallCms\Cms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use TallCms\Cms\Casts\TranslatableArray;
use TallCms\Cms\Models\Concerns\HasPreviewTokens;
use TallCms\Cms\Models\Concerns\HasPublishingWorkflow;
use TallCms\Cms\Models\Concerns\HasRevisions;
use TallCms\Cms\Models\Concerns\HasTranslatableContent;

class CmsPage extends Model
{
    use HasFactory;
    use HasPreviewTokens;
    use HasPublishingWorkflow;
    use HasRevisions;
    use HasTranslatableContent;
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
        'title',
        'slug',
        'content',
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
        'content_width',
        'author_id',
        // Publishing workflow fields
        'approved_by',
        'approved_at',
        'rejection_reason',
        'submitted_by',
        'submitted_at',
    ];

    protected $casts = [
        'content' => TranslatableArray::class,
        'published_at' => 'datetime',
        'is_homepage' => 'boolean',
        'show_breadcrumbs' => 'boolean',
        'approved_at' => 'datetime',
        'submitted_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($page) {
            if (empty($page->slug)) {
                $page->slug = $page->generateUniqueSlug($page->title);
            }

            // Ensure only one page can be marked as homepage
            if ($page->is_homepage) {
                static::where('is_homepage', true)->update(['is_homepage' => false]);
            }
        });

        static::updating(function ($page) {
            if ($page->isDirty('title') && empty($page->slug)) {
                $page->slug = $page->generateUniqueSlug($page->title);
            }

            // Ensure only one page can be marked as homepage
            if ($page->is_homepage && $page->isDirty('is_homepage')) {
                static::where('id', '!=', $page->id)->update(['is_homepage' => false]);
            }
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

    public function scopeWithSlug($query, string $slug)
    {
        // Slug is stored as JSON (translatable), so use JSON query
        // This works regardless of whether i18n is enabled
        $locale = config('app.locale', 'en');
        $driver = $query->getConnection()->getDriverName();

        return match ($driver) {
            'sqlite' => $query->whereRaw("JSON_EXTRACT(slug, '$.{$locale}') = ?", [$slug]),
            'pgsql' => $query->whereRaw("slug::jsonb ->> ? = ?", [$locale, $slug]),
            default => $query->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(slug, '$.\"" . $locale . "\"')) = ?", [$slug]),
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
                $slug = $baseSlug . '-page';
            }

            while ($this->localizedSlugExists($slug, $locale)) {
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }

            return $slug;
        }

        // Non-i18n mode: simple slug generation
        $baseSlug = Str::slug($title);
        $slug = $baseSlug;
        $counter = 1;

        while ($this->slugExists($slug)) {
            $slug = $baseSlug . '-' . $counter;
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
            'pgsql' => $query->whereRaw("slug::jsonb ->> ? = ?", [$locale, $slug]),
            default => $query->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(slug, '$.\"" . $locale . "\"')) = ?", [$slug]),
        };

        if ($this->exists) {
            $query->where('id', '!=', $this->id);
        }

        return $query->exists();
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
