<?php

declare(strict_types=1);

namespace TallCms\Cms\Models\Concerns;

use Spatie\Translatable\HasTranslations;
use TallCms\Cms\Services\LocaleRegistry;

/**
 * Trait for models with translatable content.
 *
 * Extends Spatie's HasTranslations with TallCMS-specific functionality:
 * - Locale registry integration
 * - Database-agnostic JSON queries for slug lookups
 * - Unique slug generation per locale
 */
trait HasTranslatableContent
{
    use HasTranslations;

    /**
     * Get locales from registry (not hardcoded).
     */
    public function getTranslatableLocales(): array
    {
        return app(LocaleRegistry::class)->getLocaleCodes();
    }

    /**
     * Scope to find by localized slug with fallback.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithLocalizedSlug($query, string $slug, ?string $locale = null)
    {
        $registry = app(LocaleRegistry::class);
        $locale = $locale ?? app()->getLocale();
        $fallback = $registry->getDefaultLocale();

        // Database-agnostic JSON query
        return $query->where(function ($q) use ($slug, $locale, $fallback) {
            // Try exact locale match first
            $q->where(function ($inner) use ($slug, $locale) {
                $this->whereJsonLocale($inner, 'slug', $locale, $slug);
            });

            // Fallback to default locale if different
            if ($locale !== $fallback) {
                $q->orWhere(function ($inner) use ($slug, $fallback) {
                    $this->whereJsonLocale($inner, 'slug', $fallback, $slug);
                });
            }
        });
    }

    /**
     * Database-agnostic JSON locale query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     */
    protected function whereJsonLocale($query, string $column, string $locale, string $value): void
    {
        $driver = $query->getConnection()->getDriverName();

        switch ($driver) {
            case 'sqlite':
                // SQLite uses JSON_EXTRACT
                $query->whereRaw("JSON_EXTRACT({$column}, '$.{$locale}') = ?", [$value]);
                break;

            case 'pgsql':
                // PostgreSQL uses ->> operator for text extraction
                $query->whereRaw("{$column}::jsonb ->> ? = ?", [$locale, $value]);
                break;

            default:
                // MySQL/MariaDB use JSON_UNQUOTE + JSON_EXTRACT
                $query->whereRaw("JSON_UNQUOTE(JSON_EXTRACT({$column}, '$.\"".$locale."\"')) = ?", [$value]);
        }
    }

    /**
     * Match a translatable JSON column against the current locale's text.
     *
     * Avoids ORDER BY / LIKE on the raw json value. PostgreSQL has no
     * equality or ordering operator for json; LIKE on the blob also matches
     * locale keys ("en", "de") on every driver.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereTranslatableContains($query, string $column, string $term, ?string $locale = null)
    {
        $locale = $locale ?? app()->getLocale();
        $driver = $query->getConnection()->getDriverName();
        $like = '%'.$term.'%';

        return match ($driver) {
            'sqlite' => $query->whereRaw("JSON_EXTRACT({$column}, '$.{$locale}') LIKE ?", [$like]),
            'pgsql' => $query->whereRaw("{$column}::jsonb ->> ? ILIKE ?", [$locale, $like]),
            default => $query->whereRaw("JSON_UNQUOTE(JSON_EXTRACT({$column}, '$.\"".$locale."\"')) LIKE ?", [$like]),
        };
    }

    /**
     * Check if slug exists for a specific locale.
     */
    public function localizedSlugExists(string $slug, string $locale): bool
    {
        $query = static::query();
        $this->whereJsonLocale($query, 'slug', $locale, $slug);

        if ($this->exists) {
            $query->where('id', '!=', $this->id);
        }

        return $query->exists();
    }

    /**
     * Generate unique slug for a specific locale.
     */
    public function generateUniqueSlug(string $title, ?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        $baseSlug = \Illuminate\Support\Str::slug($title);
        $slug = $baseSlug;
        $counter = 1;

        // Check reserved slugs (locale codes)
        $reserved = app(LocaleRegistry::class)->getReservedSlugs();
        if (in_array($slug, $reserved)) {
            $slug = $baseSlug.'-page';
        }

        while ($this->localizedSlugExists($slug, $locale)) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
