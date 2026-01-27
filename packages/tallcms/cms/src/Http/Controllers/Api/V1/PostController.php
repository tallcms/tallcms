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
use TallCms\Cms\Http\Requests\Api\V1\StorePostRequest;
use TallCms\Cms\Http\Requests\Api\V1\UpdatePostRequest;
use TallCms\Cms\Http\Resources\Api\V1\PostResource;
use TallCms\Cms\Http\Resources\Api\V1\RevisionResource;
use TallCms\Cms\Models\CmsPost;

class PostController extends Controller
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
        return ['status', 'author_id', 'category_id', 'is_featured', 'created_at', 'updated_at', 'trashed'];
    }

    /**
     * Allowed sort fields.
     *
     * @return array<string>
     */
    protected function allowedSorts(): array
    {
        return ['id', 'title', 'created_at', 'updated_at', 'published_at', 'views'];
    }

    /**
     * Allowed include relations.
     *
     * @return array<string>
     */
    protected function allowedIncludes(): array
    {
        return ['author', 'categories'];
    }

    /**
     * Allowed with_counts fields.
     *
     * @return array<string>
     */
    protected function allowedWithCounts(): array
    {
        return ['categories'];
    }

    /**
     * Apply a single filter to the query.
     */
    protected function applyFilter(\Illuminate\Database\Eloquent\Builder $query, string $field, mixed $value): \Illuminate\Database\Eloquent\Builder
    {
        // Handle category_id filter specially (many-to-many)
        if ($field === 'category_id') {
            $categoryIds = is_string($value) && str_contains($value, ',')
                ? explode(',', $value)
                : [$value];

            return $query->whereHas('categories', function ($q) use ($categoryIds) {
                $q->whereIn('cms_categories.id', $categoryIds);
            });
        }

        // Use parent implementation for other filters
        return parent::applyFilter($query, $field, $value);
    }

    /**
     * List all posts.
     *
     * @authenticated
     *
     * @group Posts
     *
     * @queryParam page int Page number. Example: 1
     * @queryParam per_page int Items per page (max 100). Example: 15
     * @queryParam sort string Sort field. Example: created_at
     * @queryParam order string Sort order (asc, desc). Example: desc
     * @queryParam filter[status] string Filter by status. Example: published
     * @queryParam filter[author_id] int Filter by author. Example: 1
     * @queryParam filter[category_id] int Filter by category. Example: 1
     * @queryParam filter[is_featured] bool Filter by featured status. Example: false
     * @queryParam filter[trashed] string Include soft-deleted (only, with). Example: only
     * @queryParam include string Comma-separated relations (author, categories). Example: author,categories
     * @queryParam with_counts string Comma-separated count fields (categories). Example: categories
     * @queryParam locale string Response locale for translatable fields. Example: en
     * @queryParam with_translations bool Include all translations. Example: false
     *
     * @response 200 {"data": [...], "meta": {...}, "links": {...}}
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', CmsPost::class);

        $query = CmsPost::query();

        $query = $this->applyFilters($query, $request);
        $query = $this->applySorting($query, $request);
        $query = $this->applyIncludes($query, $request);

        $posts = $this->applyPagination($query, $request);

        return response()->json([
            'data' => PostResource::collection($posts),
            'meta' => $this->paginationMeta($posts),
            'links' => $this->paginationLinks($posts),
        ]);
    }

    /**
     * Get a specific post.
     *
     * @authenticated
     *
     * @group Posts
     */
    public function show(Request $request, int $post): JsonResponse
    {
        // Include trashed posts - authorization will determine if user can view
        $postModel = CmsPost::withTrashed()->findOrFail($post);

        $this->authorize('view', $postModel);

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

            $postModel->load($includes);
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

            $postModel->loadCount($withCounts);
        }

        return $this->respondWithData(new PostResource($postModel));
    }

    /**
     * Get post revisions.
     *
     * @authenticated
     *
     * @group Posts
     */
    public function revisions(Request $request, int $post): JsonResponse
    {
        $postModel = CmsPost::withTrashed()->findOrFail($post);

        $this->authorize('viewRevisions', $postModel);

        $revisions = $postModel->revisions()
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
     * Create a new post.
     *
     * @authenticated
     *
     * @group Posts
     */
    public function store(StorePostRequest $request): JsonResponse
    {
        $this->authorize('create', CmsPost::class);

        $data = $this->prepareTranslatableData($request);
        $categoryIds = $data['category_ids'] ?? [];
        unset($data['category_ids']);

        $post = CmsPost::create(array_merge($data, [
            'author_id' => $request->user()->id,
        ]));

        if (! empty($categoryIds)) {
            $post->categories()->sync($categoryIds);
        }

        return $this->respondCreated(new PostResource($post->fresh(['categories'])));
    }

    /**
     * Update a post.
     *
     * @authenticated
     *
     * @group Posts
     */
    public function update(UpdatePostRequest $request, int $post): JsonResponse
    {
        $postModel = CmsPost::findOrFail($post);

        $this->authorize('update', $postModel);

        $data = $this->prepareTranslatableData($request);
        $categoryIds = $data['category_ids'] ?? null;
        unset($data['category_ids']);

        $postModel->update($data);

        if ($categoryIds !== null) {
            $postModel->categories()->sync($categoryIds);
        }

        return $this->respondWithData(new PostResource($postModel->fresh(['categories'])));
    }

    /**
     * Soft-delete a post.
     *
     * @authenticated
     *
     * @group Posts
     */
    public function destroy(int $post): JsonResponse
    {
        $postModel = CmsPost::findOrFail($post);

        $this->authorize('delete', $postModel);

        $postModel->delete();

        return $this->respondWithMessage('Post deleted successfully');
    }

    /**
     * Force-delete a post.
     *
     * @authenticated
     *
     * @group Posts
     */
    public function forceDestroy(int $post): JsonResponse
    {
        $postModel = CmsPost::withTrashed()->findOrFail($post);

        $this->authorize('forceDelete', $postModel);

        $postModel->forceDelete();

        return $this->respondWithMessage('Post permanently deleted');
    }

    /**
     * Restore a soft-deleted post.
     *
     * @authenticated
     *
     * @group Posts
     */
    public function restore(int $post): JsonResponse
    {
        $postModel = CmsPost::withTrashed()->findOrFail($post);

        $this->authorize('restore', $postModel);

        $postModel->restore();

        return $this->respondWithData(new PostResource($postModel->fresh()));
    }

    /**
     * Publish a post.
     *
     * @authenticated
     *
     * @group Posts
     */
    public function publish(Request $request, int $post): JsonResponse
    {
        $postModel = CmsPost::findOrFail($post);

        $this->authorize('update', $postModel);

        if ($postModel->isPending()) {
            $this->authorize('approve', $postModel);
        }

        $postModel->approve($request->user());

        return $this->respondWithData(new PostResource($postModel->fresh()));
    }

    /**
     * Unpublish a post.
     *
     * @authenticated
     *
     * @group Posts
     */
    public function unpublish(int $post): JsonResponse
    {
        $postModel = CmsPost::findOrFail($post);

        $this->authorize('update', $postModel);

        $postModel->update([
            'status' => \TallCms\Cms\Enums\ContentStatus::Draft,
            'published_at' => null,
        ]);

        return $this->respondWithData(new PostResource($postModel->fresh()));
    }

    /**
     * Submit a post for review.
     *
     * @authenticated
     *
     * @group Posts
     */
    public function submitForReview(Request $request, int $post): JsonResponse
    {
        $postModel = CmsPost::findOrFail($post);

        $this->authorize('submitForReview', $postModel);

        $postModel->submitForReview($request->user());

        return $this->respondWithData(new PostResource($postModel->fresh()));
    }

    /**
     * Approve a post.
     *
     * @authenticated
     *
     * @group Posts
     */
    public function approve(Request $request, int $post): JsonResponse
    {
        $postModel = CmsPost::findOrFail($post);

        $this->authorize('approve', $postModel);

        $postModel->approve($request->user());

        return $this->respondWithData(new PostResource($postModel->fresh()));
    }

    /**
     * Reject a post.
     *
     * @authenticated
     *
     * @group Posts
     */
    public function reject(Request $request, int $post): JsonResponse
    {
        $postModel = CmsPost::findOrFail($post);

        $this->authorize('approve', $postModel);

        $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $postModel->reject($request->input('reason'));

        return $this->respondWithData(new PostResource($postModel->fresh()));
    }

    /**
     * Restore a post revision.
     *
     * @authenticated
     *
     * @group Posts
     */
    public function restoreRevision(Request $request, int $post, int $revision): JsonResponse
    {
        $postModel = CmsPost::withTrashed()->findOrFail($post);

        $this->authorize('restoreRevision', $postModel);

        $revisionModel = $postModel->revisions()->findOrFail($revision);

        $postModel->restoreRevision($revisionModel);

        return $this->respondWithData(new PostResource($postModel->fresh()));
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
            $translatableFields = ['title', 'slug', 'excerpt', 'content', 'meta_title', 'meta_description'];

            foreach ($translatableFields as $field) {
                if (isset($data[$field])) {
                    $data[$field] = [$locale => $data[$field]];
                }
            }
        }

        return $data;
    }
}
