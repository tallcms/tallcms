<?php

declare(strict_types=1);

namespace TallCms\Cms\Http\Controllers\Api\V1\Concerns;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait HandlesPagination
{
    /**
     * Default items per page.
     */
    protected function defaultPerPage(): int
    {
        return 15;
    }

    /**
     * Apply pagination to query.
     */
    protected function applyPagination(Builder $query, Request $request): LengthAwarePaginator
    {
        $maxPerPage = (int) config('tallcms.api.max_per_page', 100);
        $perPage = min(
            (int) $request->input('per_page', $this->defaultPerPage()),
            $maxPerPage
        );

        // Ensure minimum of 1
        $perPage = max($perPage, 1);

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Format pagination metadata.
     *
     * @return array<string, mixed>
     */
    protected function paginationMeta(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'from' => $paginator->firstItem(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'to' => $paginator->lastItem(),
            'total' => $paginator->total(),
        ];
    }

    /**
     * Format pagination links.
     *
     * @return array<string, string|null>
     */
    protected function paginationLinks(LengthAwarePaginator $paginator): array
    {
        return [
            'first' => $paginator->url(1),
            'last' => $paginator->url($paginator->lastPage()),
            'prev' => $paginator->previousPageUrl(),
            'next' => $paginator->nextPageUrl(),
        ];
    }
}
