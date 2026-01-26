<?php

declare(strict_types=1);

namespace TallCms\Cms\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use TallCms\Cms\Models\CmsPage;
use TallCms\Cms\Models\CmsPost;
use TallCms\Cms\Services\SearchHighlighter;
use TallCms\Cms\Services\SeoService;

#[Layout('tallcms::layouts.app')]
class SearchResults extends Component
{
    use WithPagination;

    public string $q = '';

    public string $type = 'all';

    public bool $searchAvailable = true;

    protected $queryString = [
        'q' => ['except' => ''],
        'type' => ['except' => 'all'],
    ];

    public function mount(): void
    {
        // Check if search is enabled in config
        if (! config('tallcms.search.enabled', true)) {
            abort(404);
        }

        // Check if Scout is properly configured (database driver required)
        if (config('scout.driver') !== 'database') {
            $this->searchAvailable = false;
        }
    }

    public function updatingQ(): void
    {
        $this->resetPage();
    }

    public function updatingType(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $results = collect();
        $minLength = config('tallcms.search.min_query_length', 2);
        $perPage = config('tallcms.search.results_per_page', 10);
        $maxResultsPerType = config('tallcms.search.max_results_per_type', 50);

        // Only search if Scout is properly configured
        if ($this->searchAvailable && strlen($this->q) >= $minLength) {
            $searchableTypes = config('tallcms.search.searchable_types', ['pages', 'posts']);

            // Search pages - limit results to avoid memory issues
            if (($this->type === 'all' || $this->type === 'pages') && in_array('pages', $searchableTypes)) {
                $pages = CmsPage::search($this->q)
                    ->query(fn ($query) => $query->published())
                    ->take($maxResultsPerType)
                    ->get()
                    ->map(fn ($page) => $this->formatResult($page, 'page'));
                $results = $results->merge($pages);
            }

            // Search posts - limit results to avoid memory issues
            if (($this->type === 'all' || $this->type === 'posts') && in_array('posts', $searchableTypes)) {
                $posts = CmsPost::search($this->q)
                    ->query(fn ($query) => $query->published())
                    ->take($maxResultsPerType)
                    ->get()
                    ->map(fn ($post) => $this->formatResult($post, 'post'));
                $results = $results->merge($posts);
            }
        }

        // Manual pagination of combined results
        // Note: Total count is capped at maxResultsPerType per model
        $page = $this->getPage();
        $paginated = new LengthAwarePaginator(
            $results->forPage($page, $perPage)->values(),
            $results->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return view('tallcms::livewire.search-results', [
            'results' => $paginated,
            'query' => $this->q,
            'highlighter' => app(SearchHighlighter::class),
            'searchAvailable' => $this->searchAvailable,
        ]);
    }

    protected function formatResult($model, string $type): array
    {
        return [
            'model' => $model,
            'type' => $type,
            'excerpt' => $model->search_excerpt ?? $model->excerpt ?? $model->meta_description ?? '',
            'url' => $type === 'page'
                ? url(tallcms_localized_url($model->slug))
                : SeoService::getPostUrl($model),
        ];
    }
}
