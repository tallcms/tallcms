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
     * which columns to search with LIKE queries.
     *
     * IMPORTANT: Only include plain text columns here, NOT JSON/translatable
     * columns like title, slug, excerpt. LIKE queries on JSON columns will:
     * - Fail on PostgreSQL (JSONB doesn't support LIKE without casting)
     * - Match JSON keys like "en", "es" causing false positives
     *
     * The search_content column contains pre-extracted text from all locales
     * and all translatable fields (title, excerpt, meta fields, content blocks).
     */
    public function toSearchableArray(): array
    {
        return [
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
