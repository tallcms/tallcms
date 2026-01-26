<?php

declare(strict_types=1);

namespace TallCms\Cms\Models\Concerns;

use Illuminate\Support\Str;
use Laravel\Scout\Searchable;

trait HasSearchableContent
{
    use Searchable;

    /**
     * Get the indexable data array for the model.
     *
     * Scout's database driver uses the KEYS of this array to determine
     * which columns to search with LIKE queries. The VALUES are used
     * by other drivers (Algolia, Meilisearch) for indexing.
     *
     * Note: search_content is plain text (not JSON/translatable) containing
     * concatenated content from all locales.
     */
    public function toSearchableArray(): array
    {
        // Keys determine which columns Scout DB driver searches
        return [
            'title' => $this->title ?? '',
            'slug' => $this->slug ?? '',
            'search_content' => $this->search_content ?? '',
        ];
    }

    /**
     * Determine if the model should be searchable.
     */
    public function shouldBeSearchable(): bool
    {
        return true;
    }

    /**
     * Get a display-friendly excerpt for search results.
     * Uses model's translatable fields (NOT search_content, which is values-only).
     */
    public function getSearchExcerptAttribute(): ?string
    {
        // Use existing translatable fields for display (in current locale)
        return $this->excerpt
            ?? $this->meta_description
            ?? Str::limit(strip_tags($this->search_content ?? ''), 200);
    }
}
