<?php

declare(strict_types=1);

namespace TallCms\Cms\Observers;

use Illuminate\Database\Eloquent\Model;
use TallCms\Cms\Services\ContentIndexer;
use TallCms\Cms\Services\LocaleRegistry;

class SearchContentObserver
{
    /**
     * Separator between locale content chunks.
     * Using ||| to avoid matching common text patterns.
     */
    protected const LOCALE_SEPARATOR = ' ||| ';

    public function __construct(
        protected ContentIndexer $indexer,
        protected LocaleRegistry $localeRegistry
    ) {}

    public function saving(Model $model): void
    {
        if (! config('tallcms.search.enabled', true)) {
            return;
        }

        // Build search content for each locale, then concatenate VALUES ONLY
        // This avoids LIKE matching JSON keys like "en", "es", etc.
        $contentParts = [];
        $locales = $this->localeRegistry->getLocaleCodes();

        foreach ($locales as $locale) {
            $title = $this->getTranslation($model, 'title', $locale);
            $excerpt = $this->getTranslation($model, 'excerpt', $locale);
            $metaTitle = $this->getTranslation($model, 'meta_title', $locale);
            $metaDescription = $this->getTranslation($model, 'meta_description', $locale);
            $content = $this->getTranslation($model, 'content', $locale);

            $localeContent = $this->indexer->buildSearchContent(
                $title,
                $excerpt,
                $metaTitle,
                $metaDescription,
                $content
            );

            if (! empty($localeContent)) {
                $contentParts[] = $localeContent;
            }
        }

        // Store as plain text with separator (NOT JSON)
        $model->search_content = implode(self::LOCALE_SEPARATOR, $contentParts);
    }

    protected function getTranslation(Model $model, string $key, string $locale): mixed
    {
        if (! in_array($key, $model->translatable ?? [])) {
            return $model->getAttribute($key);
        }

        try {
            return $model->getTranslation($key, $locale, false);
        } catch (\Throwable) {
            return null;
        }
    }
}
