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
use TallCms\Cms\Http\Requests\Api\V1\StorePageRequest;
use TallCms\Cms\Http\Requests\Api\V1\UpdatePageRequest;
use TallCms\Cms\Http\Resources\Api\V1\PageResource;
use TallCms\Cms\Http\Resources\Api\V1\RevisionResource;
use TallCms\Cms\Models\CmsPage;
use TallCms\Cms\Models\CmsRevision;

class PageController extends Controller
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
        return ['status', 'author_id', 'parent_id', 'is_homepage', 'created_at', 'updated_at', 'trashed'];
    }

    /**
     * Allowed sort fields.
     *
     * @return array<string>
     */
    protected function allowedSorts(): array
    {
        return ['id', 'title', 'created_at', 'updated_at', 'published_at', 'sort_order'];
    }

    /**
     * Allowed include relations.
     *
     * @return array<string>
     */
    protected function allowedIncludes(): array
    {
        return ['parent', 'children', 'author'];
    }

    /**
     * Allowed with_counts fields.
     *
     * @return array<string>
     */
    protected function allowedWithCounts(): array
    {
        return ['children'];
    }

    /**
     * List all pages.
     *
     * @authenticated
     *
     * @group Pages
     *
     * @queryParam page int Page number. Example: 1
     * @queryParam per_page int Items per page (max 100). Example: 15
     * @queryParam sort string Sort field. Example: created_at
     * @queryParam order string Sort order (asc, desc). Example: desc
     * @queryParam filter[status] string Filter by status. Example: published
     * @queryParam filter[author_id] int Filter by author. Example: 1
     * @queryParam filter[parent_id] int Filter by parent. Example: null
     * @queryParam filter[is_homepage] bool Filter by homepage status. Example: false
     * @queryParam filter[trashed] string Include soft-deleted (only, with). Example: only
     * @queryParam include string Comma-separated relations (parent, children, author). Example: author
     * @queryParam with_counts string Comma-separated count fields (children). Example: children
     * @queryParam locale string Response locale for translatable fields. Example: en
     * @queryParam with_translations bool Include all translations. Example: false
     *
     * @response 200 {"data": [...], "meta": {...}, "links": {...}}
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', CmsPage::class);

        $query = CmsPage::query();

        $query = $this->applyFilters($query, $request);
        $query = $this->applySorting($query, $request);
        $query = $this->applyIncludes($query, $request);

        $pages = $this->applyPagination($query, $request);

        return response()->json([
            'data' => PageResource::collection($pages),
            'meta' => $this->paginationMeta($pages),
            'links' => $this->paginationLinks($pages),
        ]);
    }

    /**
     * Get a specific page.
     *
     * @authenticated
     *
     * @group Pages
     *
     * @urlParam page int required The page ID. Example: 1
     * @queryParam include string Comma-separated relations (parent, children, author). Example: author
     * @queryParam with_counts string Comma-separated count fields (children). Example: children
     * @queryParam locale string Response locale for translatable fields. Example: en
     * @queryParam with_translations bool Include all translations. Example: false
     *
     * @response 200 {"data": {...}}
     */
    public function show(Request $request, int $page): JsonResponse
    {
        // Include trashed pages - authorization will determine if user can view
        $pageModel = CmsPage::withTrashed()->findOrFail($page);

        $this->authorize('view', $pageModel);

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

            $pageModel->load($includes);
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

            $pageModel->loadCount($withCounts);
        }

        return $this->respondWithData(new PageResource($pageModel));
    }

    /**
     * Get page revisions.
     *
     * @authenticated
     *
     * @group Pages
     *
     * @urlParam page int required The page ID. Example: 1
     * @queryParam page int Page number. Example: 1
     * @queryParam per_page int Items per page (max 100). Example: 15
     *
     * @response 200 {"data": [...], "meta": {...}, "links": {...}}
     */
    public function revisions(Request $request, int $page): JsonResponse
    {
        $pageModel = CmsPage::withTrashed()->findOrFail($page);

        $this->authorize('viewRevisions', $pageModel);

        $revisions = $pageModel->revisions()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(min((int) $request->input('per_page', 15), 100))
            ->withQueryString();

        return response()->json([
            'data' => RevisionResource::collection($revisions),
            'meta' => $this->paginationMeta($revisions),
            'links' => $this->paginationLinks($revisions),
        ]);
    }

    /**
     * Create a new page.
     *
     * @authenticated
     *
     * @group Pages
     */
    public function store(StorePageRequest $request): JsonResponse
    {
        $this->authorize('create', CmsPage::class);

        $data = $this->prepareTranslatableData($request);

        $page = CmsPage::create(array_merge($data, [
            'author_id' => $request->user()->id,
        ]));

        return $this->respondCreated(new PageResource($page->fresh()));
    }

    /**
     * Update a page.
     *
     * @authenticated
     *
     * @group Pages
     */
    public function update(UpdatePageRequest $request, int $page): JsonResponse
    {
        $pageModel = CmsPage::findOrFail($page);

        $this->authorize('update', $pageModel);

        $data = $this->prepareTranslatableData($request);

        $pageModel->update($data);

        return $this->respondWithData(new PageResource($pageModel->fresh()));
    }

    /**
     * Soft-delete a page.
     *
     * @authenticated
     *
     * @group Pages
     */
    public function destroy(int $page): JsonResponse
    {
        $pageModel = CmsPage::findOrFail($page);

        $this->authorize('delete', $pageModel);

        $pageModel->delete();

        return $this->respondWithMessage('Page deleted successfully');
    }

    /**
     * Force-delete a page.
     *
     * @authenticated
     *
     * @group Pages
     */
    public function forceDestroy(int $page): JsonResponse
    {
        $pageModel = CmsPage::withTrashed()->findOrFail($page);

        $this->authorize('forceDelete', $pageModel);

        $pageModel->forceDelete();

        return $this->respondWithMessage('Page permanently deleted');
    }

    /**
     * Restore a soft-deleted page.
     *
     * @authenticated
     *
     * @group Pages
     */
    public function restore(int $page): JsonResponse
    {
        $pageModel = CmsPage::withTrashed()->findOrFail($page);

        $this->authorize('restore', $pageModel);

        $pageModel->restore();

        return $this->respondWithData(new PageResource($pageModel->fresh()));
    }

    /**
     * Publish a page.
     *
     * @authenticated
     *
     * @group Pages
     */
    public function publish(Request $request, int $page): JsonResponse
    {
        $pageModel = CmsPage::findOrFail($page);

        $this->authorize('update', $pageModel);

        // Check if user can directly publish or needs approval
        if ($pageModel->isPending()) {
            $this->authorize('approve', $pageModel);
        }

        $pageModel->approve($request->user());

        return $this->respondWithData(new PageResource($pageModel->fresh()));
    }

    /**
     * Unpublish a page.
     *
     * @authenticated
     *
     * @group Pages
     */
    public function unpublish(int $page): JsonResponse
    {
        $pageModel = CmsPage::findOrFail($page);

        $this->authorize('update', $pageModel);

        $pageModel->update([
            'status' => \TallCms\Cms\Enums\ContentStatus::Draft,
            'published_at' => null,
        ]);

        return $this->respondWithData(new PageResource($pageModel->fresh()));
    }

    /**
     * Submit a page for review.
     *
     * @authenticated
     *
     * @group Pages
     */
    public function submitForReview(Request $request, int $page): JsonResponse
    {
        $pageModel = CmsPage::findOrFail($page);

        $this->authorize('submitForReview', $pageModel);

        $pageModel->submitForReview($request->user());

        return $this->respondWithData(new PageResource($pageModel->fresh()));
    }

    /**
     * Approve a page.
     *
     * @authenticated
     *
     * @group Pages
     */
    public function approve(Request $request, int $page): JsonResponse
    {
        $pageModel = CmsPage::findOrFail($page);

        $this->authorize('approve', $pageModel);

        $pageModel->approve($request->user());

        return $this->respondWithData(new PageResource($pageModel->fresh()));
    }

    /**
     * Reject a page.
     *
     * @authenticated
     *
     * @group Pages
     */
    public function reject(Request $request, int $page): JsonResponse
    {
        $pageModel = CmsPage::findOrFail($page);

        $this->authorize('approve', $pageModel);

        $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $pageModel->reject($request->input('reason'));

        return $this->respondWithData(new PageResource($pageModel->fresh()));
    }

    /**
     * Restore a page revision.
     *
     * @authenticated
     *
     * @group Pages
     */
    public function restoreRevision(Request $request, int $page, int $revision): JsonResponse
    {
        $pageModel = CmsPage::withTrashed()->findOrFail($page);

        $this->authorize('restoreRevision', $pageModel);

        $revisionModel = $pageModel->revisions()->findOrFail($revision);

        $pageModel->restoreRevision($revisionModel);

        return $this->respondWithData(new PageResource($pageModel->fresh()));
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

        // Handle translations object mode
        if (isset($data['translations'])) {
            $translations = $data['translations'];
            unset($data['translations']);

            // Convert translations to Spatie Translatable format
            foreach ($translations as $field => $values) {
                $data[$field] = $values;
            }

            return $data;
        }

        // Handle single-locale mode
        if ($locale) {
            $translatableFields = ['title', 'slug', 'content', 'meta_title', 'meta_description'];

            foreach ($translatableFields as $field) {
                if (isset($data[$field])) {
                    $data[$field] = [$locale => $data[$field]];
                }
            }
        }

        return $data;
    }
}
