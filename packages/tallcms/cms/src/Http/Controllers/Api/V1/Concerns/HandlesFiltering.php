<?php

declare(strict_types=1);

namespace TallCms\Cms\Http\Controllers\Api\V1\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait HandlesFiltering
{
    /**
     * Allowed filter fields per resource.
     *
     * @return array<string>
     */
    abstract protected function allowedFilters(): array;

    /**
     * Apply filters to query with allowlist enforcement.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    protected function applyFilters(Builder $query, Request $request): Builder
    {
        $filters = $request->input('filter', []);

        if (empty($filters) || ! is_array($filters)) {
            return $query;
        }

        $allowed = $this->allowedFilters();

        // Check for invalid filters
        $invalid = array_diff(array_keys($filters), $allowed);

        if (! empty($invalid)) {
            abort(400, 'Invalid filter field(s): '.implode(', ', $invalid).'. Allowed: '.implode(', ', $allowed));
        }

        foreach ($filters as $field => $value) {
            $query = $this->applyFilter($query, $field, $value);
        }

        return $query;
    }

    /**
     * Apply a single filter to the query.
     */
    protected function applyFilter(Builder $query, string $field, mixed $value): Builder
    {
        // Handle special filters
        if ($field === 'trashed') {
            return $this->applyTrashedFilter($query, $value);
        }

        // Handle date range filters (created_at, updated_at, published_at)
        if (str_ends_with($field, '_at')) {
            return $this->applyDateFilter($query, $field, $value);
        }

        // Handle boolean fields
        if (in_array($field, ['is_homepage', 'is_featured', 'has_variants'], true)) {
            return $query->where($field, filter_var($value, FILTER_VALIDATE_BOOLEAN));
        }

        // Handle comma-separated values for IN queries
        if (is_string($value) && str_contains($value, ',')) {
            return $query->whereIn($field, explode(',', $value));
        }

        // Standard equality filter
        return $query->where($field, $value);
    }

    /**
     * Apply trashed filter for soft-deleted records.
     */
    protected function applyTrashedFilter(Builder $query, mixed $value): Builder
    {
        return match ($value) {
            'only' => $query->onlyTrashed(),
            'with' => $query->withTrashed(),
            default => $query,
        };
    }

    /**
     * Apply date range filter.
     *
     * Supports:
     * - Single date: filter[created_at]=2024-01-01 (exact day)
     * - Range: filter[created_at]=2024-01-01,2024-01-31
     * - From: filter[created_at]=2024-01-01,
     * - To: filter[created_at]=,2024-01-31
     */
    protected function applyDateFilter(Builder $query, string $field, mixed $value): Builder
    {
        if (str_contains((string) $value, ',')) {
            [$from, $to] = array_pad(explode(',', (string) $value, 2), 2, null);

            if ($from) {
                $query->whereDate($field, '>=', $from);
            }

            if ($to) {
                $query->whereDate($field, '<=', $to);
            }

            return $query;
        }

        // Single date = exact day
        return $query->whereDate($field, $value);
    }
}
