<?php

declare(strict_types=1);

namespace TallCms\Cms\Http\Controllers\Api\V1\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait HandlesSorting
{
    /**
     * Allowed sort fields per resource.
     *
     * @return array<string>
     */
    abstract protected function allowedSorts(): array;

    /**
     * Default sort field.
     */
    protected function defaultSort(): string
    {
        return 'created_at';
    }

    /**
     * Default sort order.
     */
    protected function defaultOrder(): string
    {
        return 'desc';
    }

    /**
     * Apply sorting to query with allowlist enforcement.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    protected function applySorting(Builder $query, Request $request): Builder
    {
        $sort = $request->input('sort', $this->defaultSort());
        $order = strtolower($request->input('order', $this->defaultOrder()));

        // Validate sort field
        $allowed = $this->allowedSorts();

        if (! in_array($sort, $allowed, true)) {
            abort(400, 'Invalid sort field: '.$sort.'. Allowed: '.implode(', ', $allowed));
        }

        // Validate order direction
        if (! in_array($order, ['asc', 'desc'], true)) {
            $order = 'desc';
        }

        return $query->orderBy($sort, $order);
    }
}
