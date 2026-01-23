<?php

declare(strict_types=1);

namespace TallCms\Cms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use TallCms\Cms\Casts\TranslatableArray;
use TallCms\Cms\Models\Concerns\HasPreviewTokens;
use TallCms\Cms\Models\Concerns\HasPublishingWorkflow;
use TallCms\Cms\Models\Concerns\HasRevisions;
use TallCms\Cms\Models\Concerns\HasTranslatableContent;

class CmsPost extends Model
{
    use HasFactory;
    use HasPreviewTokens;
    use HasPublishingWorkflow;
    use HasRevisions;
    use HasTranslatableContent;
    use SoftDeletes;

    protected $table = 'tallcms_posts';

    /**
     * Translatable attributes for Spatie Laravel Translatable.
     *
     * @var array<string>
     */
    public array $translatable = [
        'title',
        'slug',
        'excerpt',
        'content',
        'meta_title',
        'meta_description',
    ];

    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'content',
        'meta_title',
        'meta_description',
        'featured_image',
        'status',
        'published_at',
        'author_id',
        'is_featured',
        'views',
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
        'is_featured' => 'boolean',
        'approved_at' => 'datetime',
        'submitted_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($post) {
            if (empty($post->slug)) {
                $post->slug = $post->generateUniqueSlug($post->title);
            }
            if (empty($post->author_id)) {
                $post->author_id = auth()->id();
            }
        });

        static::updating(function ($post) {
            if ($post->isDirty('title') && empty($post->slug)) {
                $post->slug = $post->generateUniqueSlug($post->title);
            }
        });
    }

    public function author(): BelongsTo
    {
        $userModel = config('tallcms.plugin_mode.user_model', \App\Models\User::class);

        return $this->belongsTo($userModel, 'author_id');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(CmsCategory::class, 'tallcms_post_category', 'post_id', 'category_id');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
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

    public function scopeInCategory($query, $categorySlug)
    {
        return $query->whereHas('categories', function ($q) use ($categorySlug) {
            // Category slug is stored as JSON (translatable)
            $locale = config('app.locale', 'en');
            $driver = $q->getConnection()->getDriverName();

            match ($driver) {
                'sqlite' => $q->whereRaw("JSON_EXTRACT(slug, '$.{$locale}') = ?", [$categorySlug]),
                'pgsql' => $q->whereRaw("slug::jsonb ->> ? = ?", [$locale, $categorySlug]),
                default => $q->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(slug, '$.\"" . $locale . "\"')) = ?", [$categorySlug]),
            };
        });
    }

    public function getRouteKeyName(): string
    {
        // Use ID for route binding since slug is now stored as JSON (translatable)
        return 'id';
    }

    public function getReadingTimeAttribute(): int
    {
        $wordCount = str_word_count(strip_tags($this->excerpt.' '.json_encode($this->content)));

        return (int) ceil($wordCount / 200); // Assuming 200 words per minute
    }

    /**
     * Generate a unique slug from title.
     *
     * When i18n is enabled, this uses locale-aware slug generation.
     * When i18n is disabled, uses simple slug generation.
     *
     * @param  string  $title  The title to generate slug from
     * @param  string|null  $locale  The locale (only used when i18n is enabled)
     */
    public function generateUniqueSlug(string $title, ?string $locale = null): string
    {
        // When i18n is enabled, use locale-aware version
        if (tallcms_i18n_enabled()) {
            $locale = $locale ?? app()->getLocale();
            $baseSlug = Str::slug($title);
            $slug = $baseSlug;
            $counter = 1;

            // Check reserved slugs (locale codes)
            $reserved = app(\TallCms\Cms\Services\LocaleRegistry::class)->getReservedSlugs();
            if (in_array($slug, $reserved)) {
                $slug = $baseSlug . '-post';
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
}
