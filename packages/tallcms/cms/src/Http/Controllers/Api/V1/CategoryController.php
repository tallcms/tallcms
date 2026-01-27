<?php

declare(strict_types=1);

namespace TallCms\Cms\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use TallCms\Cms\Http\Controllers\Api\V1\Concerns\HandlesFiltering;
use TallCms\Cms\Http\Controllers\Api\V1\Concerns\HandlesIncludes;
use TallCms\Cms\Http\Controllers\Api\V1\Concerns\HandlesLocale;
use TallCms\Cms\Http\Controllers\Api\V1\Concerns\HandlesPagination;
use TallCms\Cms\Http\Controllers\Api\V1\Concerns\HandlesSorting;
use TallCms\Cms\Http\Requests\Api\V1\StoreCategoryRequest;
use TallCms\Cms\Http\Requests\Api\V1\UpdateCategoryRequest;
use TallCms\Cms\Http\Resources\Api\V1\CategoryResource;
use TallCms\Cms\Http\Resources\Api\V1\PostResource;
use TallCms\Cms\Models\CmsCategory;

class CategoryController extends Controller
{
    use HandlesFiltering;
    use HandlesIncludes;
    use HandlesLocale;
    use HandlesPagination;
    use HandlesSorting;

    /**
     * Allowed filter fields.
     *
     * @return array<string>
     */
    protected function allowedFilters(): array
    {
        return ['parent_id'];
    }

    /**
     * Allowed sort fields.
     *
     * @return array<string>
     */
    protected function allowedSorts(): array
    {
        return ['id', 'name', 'created_at', 'sort_order'];
    }

    /**
     * Allowed include relations.
     *
     * @return array<string>
     */
    protected function allowedIncludes(): array
    {
        return ['parent', 'children'];
    }

    /**
     * Allowed with_counts fields.
     *
     * @return array<string>
     */
    protected function allowedWithCounts(): array
    {
        return ['posts', 'children'];
    }

    /**
     * List all categories.
     *
     * @authenticated
     *
     * @group Categories
     *
     * @queryParam page int Page number. Example: 1
     * @queryParam per_page int Items per page (max 100). Example: 15
     * @queryParam sort string Sort field. Example: name
     * @queryParam order string Sort order (asc, desc). Example: asc
     * @queryParam filter[parent_id] int Filter by parent category. Example: 1
     * @queryParam include string Comma-separated relations (parent, children). Example: children
     * @queryParam with_counts string Comma-separated count fields (posts, children). Example: posts
     * @queryParam locale string Response locale for translatable fields. Example: en
     * @queryParam with_translations bool Include all translations. Example: false
     *
     * @response 200 {"data": [...], "meta": {...}, "links": {...}}
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', CmsCategory::class);

        $query = CmsCategory::query();

        $query = $this->applyFilters($query, $request);
        $query = $this->applySorting($query, $request);
        $query = $this->applyIncludes($query, $request);

        $categories = $this->applyPagination($query, $request);

        return response()->json([
            'data' => CategoryResource::collection($categories),
            'meta' => $this->paginationMeta($categories),
            'links' => $this->paginationLinks($categories),
        ]);
    }

    /**
     * Get a specific category.
     *
     * @authenticated
     *
     * @group Categories
     */
    public function show(Request $request, int $category): JsonResponse
    {
        $categoryModel = CmsCategory::findOrFail($category);

        $this->authorize('view', $categoryModel);

        // Apply includes
        $includes = $request->input('include');
        if ($includes) {
            $includes = is_array($includes) ? $includes : explode(',', $includes);
            $allowed = $this->allowedIncludes();
            $invalid = array_diff($includes, $allowed);

            if (! empty($invalid)) {
                return $this->respondWithError(
                    'Invalid include(s): '.implode(', ', $invalid),
                    'invalid_includes',
                    400
                );
            }

            $categoryModel->load($includes);
        }

        // Apply with_counts
        $withCounts = $request->input('with_counts');
        if ($withCounts) {
            $withCounts = is_array($withCounts) ? $withCounts : explode(',', $withCounts);
            $allowed = $this->allowedWithCounts();
            $invalid = array_diff($withCounts, $allowed);

            if (! empty($invalid)) {
                return $this->respondWithError(
                    'Invalid with_counts field(s): '.implode(', ', $invalid),
                    'invalid_with_counts',
                    400
                );
            }

            $categoryModel->loadCount($withCounts);
        }

        return $this->respondWithData(new CategoryResource($categoryModel));
    }

    /**
     * Get posts for a category.
     *
     * @authenticated
     *
     * @group Categories
     */
    public function posts(Request $request, int $category): JsonResponse
    {
        $categoryModel = CmsCategory::findOrFail($category);

        $this->authorize('view', $categoryModel);

        $posts = $categoryModel->posts()
            ->orderBy('published_at', 'desc')
            ->paginate(min((int) $request->input('per_page', 15), 100))
            ->withQueryString();

        return response()->json([
            'data' => PostResource::collection($posts),
            'meta' => $this->paginationMeta($posts),
            'links' => $this->paginationLinks($posts),
        ]);
    }

    /**
     * Create a new category.
     *
     * @authenticated
     *
     * @group Categories
     */
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $this->authorize('create', CmsCategory::class);

        $data = $this->prepareTranslatableData($request);

        $category = CmsCategory::create($data);

        return $this->respondCreated(new CategoryResource($category->fresh()));
    }

    /**
     * Update a category.
     *
     * @authenticated
     *
     * @group Categories
     */
    public function update(UpdateCategoryRequest $request, int $category): JsonResponse
    {
        $categoryModel = CmsCategory::findOrFail($category);

        $this->authorize('update', $categoryModel);

        $data = $this->prepareTranslatableData($request);

        $categoryModel->update($data);

        return $this->respondWithData(new CategoryResource($categoryModel->fresh()));
    }

    /**
     * Delete a category (hard delete).
     *
     * @authenticated
     *
     * @group Categories
     */
    public function destroy(int $category): JsonResponse
    {
        $categoryModel = CmsCategory::findOrFail($category);

        $this->authorize('delete', $categoryModel);

        $categoryModel->delete();

        return $this->respondWithMessage('Category deleted successfully');
    }

    /**
     * Prepare translatable data from request.
     *
     * @return array<string, mixed>
     */
    protected function prepareTranslatableData(Request $request): array
    {
        $locale = $this->getLocale($request);
        $data = $request->validated();

        if (isset($data['translations'])) {
            $translations = $data['translations'];
            unset($data['translations']);

            foreach ($translations as $field => $values) {
                $data[$field] = $values;
            }

            return $data;
        }

        if ($locale) {
            $translatableFields = ['name', 'slug', 'description'];

            foreach ($translatableFields as $field) {
                if (isset($data[$field])) {
                    $data[$field] = [$locale => $data[$field]];
                }
            }
        }

        return $data;
    }
}
