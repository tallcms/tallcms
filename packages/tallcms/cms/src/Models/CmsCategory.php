<?php

declare(strict_types=1);

namespace TallCms\Cms\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use TallCms\Cms\Models\Concerns\HasTranslatableContent;

class CmsCategory extends Model
{
    use HasTranslatableContent;
    use SoftDeletes;

    protected $table = 'tallcms_categories';

    /**
     * Translatable attributes for Spatie Laravel Translatable.
     *
     * @var array<string>
     */
    public array $translatable = [
        'name',
        'slug',
        'description',
    ];

    protected $fillable = [
        'name',
        'slug',
        'description',
        'color',
        'parent_id',
        'sort_order',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($category) {
            if (empty($category->slug)) {
                $category->slug = $category->generateUniqueSlug($category->name);
            }
        });

        static::updating(function ($category) {
            if ($category->isDirty('name') && empty($category->slug)) {
                $category->slug = $category->generateUniqueSlug($category->name);
            }
        });
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(CmsCategory::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(CmsCategory::class, 'parent_id')->orderBy('sort_order');
    }

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(CmsPost::class, 'tallcms_post_category', 'category_id', 'post_id');
    }

    /**
     * Option list for category selects.
     *
     * Loads models and reads the translated name. A relationship option query
     * selects distinct category rows, and PostgreSQL cannot compare json columns.
     *
     * @param  list<int|string>|null  $ids
     * @param  (callable(Builder<CmsCategory>): void)|null  $constrain
     * @return array<int|string, string>
     */
    public static function translatedSelectOptions(?string $search = null, ?array $ids = null, ?callable $constrain = null): array
    {
        $query = static::query()->orderBy('sort_order');

        if ($ids !== null) {
            $ids = array_values(array_filter(
                $ids,
                fn (mixed $id): bool => filled($id),
            ));

            if ($ids === []) {
                return [];
            }

            $query->whereKey($ids);
        }

        if (filled($search)) {
            $query->whereTranslatableContains('name', $search);
        }

        if ($constrain !== null) {
            $constrain($query);
        }

        return $query->get()
            ->mapWithKeys(fn (self $category): array => [
                $category->getKey() => (string) $category->name,
            ])
            ->all();
    }

    public function scopeForPublicSlug($query, string $slug)
    {
        if (tallcms_i18n_enabled()) {
            return $query->withLocalizedSlug($slug);
        }

        return $query->withSlug($slug);
    }

    public function scopeWithSlug($query, string $slug)
    {
        // Slug is stored as JSON. Match the runtime locale, which
        // SetLocaleMiddleware sets from the URL. config('app.locale') stays
        // on the default locale.
        $locale = app()->getLocale();
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

    /**
     * Generate a unique slug from name.
     *
     * When i18n is enabled, this uses locale-aware slug generation.
     * When i18n is disabled, uses simple slug generation.
     *
     * @param  string  $name  The name to generate slug from
     * @param  string|null  $locale  The locale (only used when i18n is enabled)
     */
    public function generateUniqueSlug(string $name, ?string $locale = null): string
    {
        // When i18n is enabled, use locale-aware version
        if (tallcms_i18n_enabled()) {
            $locale = $locale ?? app()->getLocale();
            $baseSlug = Str::slug($name);
            $slug = $baseSlug;
            $counter = 1;

            // Check reserved slugs (locale codes)
            $reserved = app(\TallCms\Cms\Services\LocaleRegistry::class)->getReservedSlugs();
            if (in_array($slug, $reserved)) {
                $slug = $baseSlug.'-category';
            }

            while ($this->localizedSlugExists($slug, $locale)) {
                $slug = $baseSlug.'-'.$counter;
                $counter++;
            }

            return $slug;
        }

        // Non-i18n mode: simple slug generation
        $baseSlug = Str::slug($name);
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
}
