<?php

declare(strict_types=1);

namespace TallCms\Cms\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use TallCms\Cms\Http\Controllers\Api\V1\Concerns\HandlesIncludes;
use TallCms\Cms\Http\Controllers\Api\V1\Concerns\HandlesLocale;
use TallCms\Cms\Http\Controllers\Api\V1\Concerns\HandlesPagination;
use TallCms\Cms\Http\Controllers\Api\V1\Concerns\HandlesSorting;
use TallCms\Cms\Http\Requests\Api\V1\StoreMediaCollectionRequest;
use TallCms\Cms\Http\Requests\Api\V1\UpdateMediaCollectionRequest;
use TallCms\Cms\Http\Resources\Api\V1\MediaCollectionResource;
use TallCms\Cms\Models\MediaCollection;
use TallCms\Cms\Models\TallcmsMedia;

class MediaCollectionController extends Controller
{
    use HandlesIncludes;
    use HandlesLocale;
    use HandlesPagination;
    use HandlesSorting;

    /**
     * Allowed sort fields.
     *
     * @return array<string>
     */
    protected function allowedSorts(): array
    {
        return ['id', 'name', 'created_at'];
    }

    /**
     * Allowed include relations.
     *
     * @return array<string>
     */
    protected function allowedIncludes(): array
    {
        return ['media'];
    }

    /**
     * Allowed with_counts fields.
     *
     * @return array<string>
     */
    protected function allowedWithCounts(): array
    {
        return ['media'];
    }

    /**
     * List all media collections.
     *
     * @authenticated
     *
     * @group Media Collections
     *
     * @queryParam page int Page number. Example: 1
     * @queryParam per_page int Items per page (max 100). Example: 15
     * @queryParam sort string Sort field. Example: name
     * @queryParam order string Sort order (asc, desc). Example: asc
     * @queryParam include string Comma-separated relations (media). Example: media
     * @queryParam with_counts string Comma-separated count fields (media). Example: media
     * @queryParam locale string Response locale for translatable fields. Example: en
     * @queryParam with_translations bool Include all translations. Example: false
     *
     * @response 200 {"data": [...], "meta": {...}, "links": {...}}
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', TallcmsMedia::class);

        $query = MediaCollection::query();

        $query = $this->applySorting($query, $request);
        $query = $this->applyIncludes($query, $request);

        $collections = $this->applyPagination($query, $request);

        return response()->json([
            'data' => MediaCollectionResource::collection($collections),
            'meta' => $this->paginationMeta($collections),
            'links' => $this->paginationLinks($collections),
        ]);
    }

    /**
     * Get a specific media collection.
     *
     * @authenticated
     *
     * @group Media Collections
     */
    public function show(Request $request, int $collection): JsonResponse
    {
        $collectionModel = MediaCollection::findOrFail($collection);

        // Use viewAny since collections don't have their own policy
        $this->authorize('viewAny', TallcmsMedia::class);

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

            $collectionModel->load($includes);
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

            $collectionModel->loadCount($withCounts);
        }

        return $this->respondWithData(new MediaCollectionResource($collectionModel));
    }

    /**
     * Create a new media collection.
     *
     * @authenticated
     *
     * @group Media Collections
     */
    public function store(StoreMediaCollectionRequest $request): JsonResponse
    {
        $this->authorize('create', TallcmsMedia::class);

        $data = $this->prepareTranslatableData($request);

        $collection = MediaCollection::create($data);

        return $this->respondCreated(new MediaCollectionResource($collection->fresh()));
    }

    /**
     * Update a media collection.
     *
     * @authenticated
     *
     * @group Media Collections
     */
    public function update(UpdateMediaCollectionRequest $request, int $collection): JsonResponse
    {
        $collectionModel = MediaCollection::findOrFail($collection);

        // Use create since collections don't have their own policy
        $this->authorize('create', TallcmsMedia::class);

        $data = $this->prepareTranslatableData($request);

        $collectionModel->update($data);

        return $this->respondWithData(new MediaCollectionResource($collectionModel->fresh()));
    }

    /**
     * Delete a media collection (hard delete).
     *
     * @authenticated
     *
     * @group Media Collections
     */
    public function destroy(int $collection): JsonResponse
    {
        $collectionModel = MediaCollection::findOrFail($collection);

        // Use create since collections don't have their own policy
        $this->authorize('create', TallcmsMedia::class);

        $collectionModel->delete();

        return $this->respondWithMessage('Media collection deleted successfully');
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
            $translatableFields = ['name', 'slug'];

            foreach ($translatableFields as $field) {
                if (isset($data[$field])) {
                    $data[$field] = [$locale => $data[$field]];
                }
            }
        }

        return $data;
    }
}
