<?php

declare(strict_types=1);

namespace TallCms\Cms\Services;

use Illuminate\Support\Collection;
use TallCms\Cms\Models\TallcmsMedia;

class MediaResolver
{
    /**
     * Resolve media items from one or more collection IDs.
     *
     * @param  array|int  $collectionIds  Collection ID(s) to query
     * @param  string  $order  Sort order: 'newest', 'oldest', 'random'
     * @param  int|null  $limit  Maximum number of items to return
     * @param  string|null  $mimeType  Filter by mime type prefix (e.g., 'image/')
     */
    /**
     * Resolve media items from one or more collection IDs.
     *
     * @param  array|int  $collectionIds  Collection ID(s) to query
     * @param  string  $order  Sort order: 'newest', 'oldest', 'random'
     * @param  int|null  $limit  Maximum number of items to return
     * @param  string|null  $mimeType  Filter by mime type prefix (e.g., 'image/'), or null for images+videos only
     */
    public static function fromCollections(
        array|int $collectionIds,
        string $order = 'newest',
        ?int $limit = null,
        ?string $mimeType = 'image/'
    ): Collection {
        $ids = is_array($collectionIds) ? $collectionIds : [$collectionIds];

        if (empty($ids)) {
            return collect();
        }

        $query = TallcmsMedia::query()->inCollection($ids);

        if ($mimeType) {
            $query->ofType($mimeType);
        } else {
            // When null (media_type=all), only include images and videos (not audio, documents)
            $query->where(function ($q) {
                $q->where('mime_type', 'like', 'image/%')
                  ->orWhere('mime_type', 'like', 'video/%');
            });
        }

        $query = match ($order) {
            'newest' => $query->orderBy('created_at', 'desc'),
            'oldest' => $query->orderBy('created_at', 'asc'),
            'random' => $query->inRandomOrder(),
            default => $query->orderBy('id'),
        };

        if ($limit && $limit > 0) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Convert media collection to template-ready array format.
     *
     * @param  Collection  $media  Collection of TallcmsMedia models
     * @return array Array of image data arrays with url, webp, thumbnail, alt, etc.
     */
    public static function toTemplateArray(Collection $media): array
    {
        return $media->map(fn (TallcmsMedia $item) => [
            'url' => $item->url,
            'webp' => $item->hasVariant('large') ? $item->getVariantUrl('large') : null,
            'thumbnail' => $item->hasVariant('thumbnail') ? $item->getVariantUrl('thumbnail') : $item->url,
            'alt' => $item->alt_text ?? '',
            'caption' => $item->caption,
            'width' => $item->width,
            'height' => $item->height,
            'type' => $item->is_image ? 'image' : (str_starts_with($item->mime_type, 'video/') ? 'video' : 'file'),
            'mime_type' => $item->mime_type,
        ])->toArray();
    }
}
