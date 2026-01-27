<?php

declare(strict_types=1);

namespace TallCms\Cms\Http\Controllers\Api\V1\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait HandlesIncludes
{
    /**
     * Allowed include relations per resource.
     *
     * @return array<string>
     */
    abstract protected function allowedIncludes(): array;

    /**
     * Allowed with_counts fields per resource.
     *
     * @return array<string>
     */
    protected function allowedWithCounts(): array
    {
        return [];
    }

    /**
     * Apply eager loading to query with allowlist enforcement.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    protected function applyIncludes(Builder $query, Request $request): Builder
    {
        // Handle include parameter
        $includes = $request->input('include');

        if ($includes) {
            $includes = is_array($includes) ? $includes : explode(',', $includes);
            $allowed = $this->allowedIncludes();

            $invalid = array_diff($includes, $allowed);

            if (! empty($invalid)) {
                abort(400, 'Invalid include(s): '.implode(', ', $invalid).'. Allowed: '.implode(', ', $allowed));
            }

            $query->with($includes);
        }

        // Handle with_counts parameter (separate from includes)
        $withCounts = $request->input('with_counts');

        if ($withCounts) {
            $withCounts = is_array($withCounts) ? $withCounts : explode(',', $withCounts);
            $allowed = $this->allowedWithCounts();

            $invalid = array_diff($withCounts, $allowed);

            if (! empty($invalid)) {
                abort(400, 'Invalid with_counts field(s): '.implode(', ', $invalid).'. Allowed: '.implode(', ', $allowed));
            }

            $query->withCount($withCounts);
        }

        return $query;
    }
}
