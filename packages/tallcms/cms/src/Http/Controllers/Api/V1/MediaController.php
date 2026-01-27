<?php

declare(strict_types=1);

namespace TallCms\Cms\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use TallCms\Cms\Http\Controllers\Api\V1\Concerns\HandlesFiltering;
use TallCms\Cms\Http\Controllers\Api\V1\Concerns\HandlesIncludes;
use TallCms\Cms\Http\Controllers\Api\V1\Concerns\HandlesPagination;
use TallCms\Cms\Http\Controllers\Api\V1\Concerns\HandlesSorting;
use TallCms\Cms\Http\Requests\Api\V1\StoreMediaRequest;
use TallCms\Cms\Http\Requests\Api\V1\UpdateMediaRequest;
use TallCms\Cms\Http\Resources\Api\V1\MediaResource;
use TallCms\Cms\Models\TallcmsMedia;

class MediaController extends Controller
{
    use HandlesFiltering;
    use HandlesIncludes;
    use HandlesPagination;
    use HandlesSorting;

    /**
     * Allowed filter fields.
     *
     * @return array<string>
     */
    protected function allowedFilters(): array
    {
        return ['mime_type', 'collection_id', 'has_variants', 'created_at'];
    }

    /**
     * Allowed sort fields.
     *
     * @return array<string>
     */
    protected function allowedSorts(): array
    {
        return ['id', 'name', 'size', 'created_at'];
    }

    /**
     * Allowed include relations.
     *
     * @return array<string>
     */
    protected function allowedIncludes(): array
    {
        return ['collections'];
    }

    /**
     * Allowed with_counts fields.
     *
     * @return array<string>
     */
    protected function allowedWithCounts(): array
    {
        return ['collections'];
    }

    /**
     * Apply a single filter to the query.
     */
    protected function applyFilter(\Illuminate\Database\Eloquent\Builder $query, string $field, mixed $value): \Illuminate\Database\Eloquent\Builder
    {
        // Handle collection_id filter specially (many-to-many)
        if ($field === 'collection_id') {
            $collectionIds = is_string($value) && str_contains($value, ',')
                ? explode(',', $value)
                : [$value];

            return $query->whereHas('collections', function ($q) use ($collectionIds) {
                $q->whereIn('media_collections.id', $collectionIds);
            });
        }

        // Handle has_variants filter
        if ($field === 'has_variants') {
            $hasVariants = filter_var($value, FILTER_VALIDATE_BOOLEAN);

            return $hasVariants
                ? $query->whereNotNull('variants')->where('variants', '!=', '[]')
                : $query->where(function ($q) {
                    $q->whereNull('variants')->orWhere('variants', '[]');
                });
        }

        // Handle mime_type filter with wildcard support (e.g., image/*)
        if ($field === 'mime_type') {
            if (str_ends_with($value, '/*')) {
                $prefix = str_replace('/*', '/', $value);

                return $query->where('mime_type', 'like', $prefix.'%');
            }

            return $query->where('mime_type', $value);
        }

        return parent::applyFilter($query, $field, $value);
    }

    /**
     * List all media.
     *
     * @authenticated
     *
     * @group Media
     *
     * @queryParam page int Page number. Example: 1
     * @queryParam per_page int Items per page (max 100). Example: 15
     * @queryParam sort string Sort field. Example: created_at
     * @queryParam order string Sort order (asc, desc). Example: desc
     * @queryParam filter[mime_type] string Filter by MIME type (supports wildcard: image/*). Example: image/jpeg
     * @queryParam filter[collection_id] int Filter by collection. Example: 1
     * @queryParam filter[has_variants] bool Filter by variant status. Example: true
     * @queryParam include string Comma-separated relations (collections). Example: collections
     * @queryParam with_counts string Comma-separated count fields (collections). Example: collections
     *
     * @response 200 {"data": [...], "meta": {...}, "links": {...}}
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', TallcmsMedia::class);

        $query = TallcmsMedia::query();

        $query = $this->applyFilters($query, $request);
        $query = $this->applySorting($query, $request);
        $query = $this->applyIncludes($query, $request);

        $media = $this->applyPagination($query, $request);

        return response()->json([
            'data' => MediaResource::collection($media),
            'meta' => $this->paginationMeta($media),
            'links' => $this->paginationLinks($media),
        ]);
    }

    /**
     * Get a specific media item.
     *
     * @authenticated
     *
     * @group Media
     */
    public function show(Request $request, int $media): JsonResponse
    {
        $mediaModel = TallcmsMedia::findOrFail($media);

        $this->authorize('view', $mediaModel);

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

            $mediaModel->load($includes);
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

            $mediaModel->loadCount($withCounts);
        }

        return $this->respondWithData(new MediaResource($mediaModel));
    }

    /**
     * Upload a new media file.
     *
     * @authenticated
     *
     * @group Media
     */
    public function store(StoreMediaRequest $request): JsonResponse
    {
        $this->authorize('create', TallcmsMedia::class);

        $file = $request->file('file');
        $disk = $request->input('disk', 'public');

        // Store the file
        $path = $file->store('media', $disk);

        // Get image dimensions if it's an image
        $width = null;
        $height = null;

        if (str_starts_with($file->getMimeType(), 'image/')) {
            $imageInfo = getimagesize($file->getPathname());
            if ($imageInfo) {
                $width = $imageInfo[0];
                $height = $imageInfo[1];
            }
        }

        $media = TallcmsMedia::create([
            'name' => $request->input('name', $file->getClientOriginalName()),
            'file_name' => $file->getClientOriginalName(),
            'path' => $path,
            'disk' => $disk,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'alt_text' => $request->input('alt_text'),
            'caption' => $request->input('caption'),
            'width' => $width,
            'height' => $height,
        ]);

        // Sync collections if provided
        if ($request->has('collection_ids')) {
            $media->collections()->sync($request->input('collection_ids'));
        }

        return $this->respondCreated(new MediaResource($media->fresh(['collections'])));
    }

    /**
     * Update a media item's metadata.
     *
     * @authenticated
     *
     * @group Media
     */
    public function update(UpdateMediaRequest $request, int $media): JsonResponse
    {
        $mediaModel = TallcmsMedia::findOrFail($media);

        $this->authorize('update', $mediaModel);

        $mediaModel->update($request->only(['name', 'alt_text', 'caption']));

        // Sync collections if provided
        if ($request->has('collection_ids')) {
            $mediaModel->collections()->sync($request->input('collection_ids'));
        }

        return $this->respondWithData(new MediaResource($mediaModel->fresh(['collections'])));
    }

    /**
     * Delete a media item (hard delete).
     *
     * @authenticated
     *
     * @group Media
     */
    public function destroy(int $media): JsonResponse
    {
        $mediaModel = TallcmsMedia::findOrFail($media);

        $this->authorize('delete', $mediaModel);

        // Delete the file from storage
        Storage::disk($mediaModel->disk)->delete($mediaModel->path);

        // Delete variants if any
        if ($mediaModel->variants) {
            foreach ($mediaModel->variants as $variant => $variantPath) {
                Storage::disk($mediaModel->disk)->delete($variantPath);
            }
        }

        $mediaModel->delete();

        return $this->respondWithMessage('Media deleted successfully');
    }
}
