<?php

declare(strict_types=1);

namespace TallCms\Cms\Console\Commands;

use Illuminate\Console\Command;
use TallCms\Cms\Models\CmsPage;
use TallCms\Cms\Models\CmsPost;
use TallCms\Cms\Services\ContentIndexer;
use TallCms\Cms\Services\LocaleRegistry;

class SearchIndex extends Command
{
    protected $signature = 'tallcms:search-index
                            {--model= : Only index specific model (page, post)}';

    protected $description = 'Rebuild search_content column for all CMS content';

    public function __construct(
        protected ContentIndexer $indexer,
        protected LocaleRegistry $localeRegistry
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        if (! config('tallcms.search.enabled', true)) {
            $this->error('Search is disabled in configuration.');

            return Command::FAILURE;
        }

        $models = $this->getModelsToIndex();
        $locales = $this->localeRegistry->getLocaleCodes();

        $this->info('Rebuilding search content for '.count($models).' model(s)...');
        $this->info('Locales: '.implode(', ', $locales));

        $separator = ' ||| ';

        foreach ($models as $modelClass) {
            $this->info("Processing {$modelClass}...");

            $processed = 0;

            $modelClass::withoutGlobalScopes()
                ->chunk(100, function ($records) use ($locales, $separator, &$processed) {
                    foreach ($records as $record) {
                        $contentParts = [];

                        foreach ($locales as $locale) {
                            $title = $this->getTranslation($record, 'title', $locale);
                            $excerpt = $this->getTranslation($record, 'excerpt', $locale);
                            $metaTitle = $this->getTranslation($record, 'meta_title', $locale);
                            $metaDescription = $this->getTranslation($record, 'meta_description', $locale);
                            $content = $this->getTranslation($record, 'content', $locale);

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

                        // Direct update to avoid observer loop
                        // Store as plain text with separator (NOT JSON)
                        $record->newQuery()
                            ->where($record->getKeyName(), $record->getKey())
                            ->update(['search_content' => implode($separator, $contentParts)]);

                        $processed++;
                    }
                });

            $this->info("  Processed {$processed} records.");
        }

        $this->info('Search content rebuilt successfully.');

        return Command::SUCCESS;
    }

    protected function getModelsToIndex(): array
    {
        $model = $this->option('model');

        return match ($model) {
            'page' => [CmsPage::class],
            'post' => [CmsPost::class],
            default => [CmsPage::class, CmsPost::class],
        };
    }

    protected function getTranslation($model, string $key, string $locale): mixed
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
