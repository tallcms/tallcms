<?php

declare(strict_types=1);

namespace TallCms\Cms\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \TallCms\Cms\Models\TallcmsMedia
 */
class MediaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'path' => $this->path,
            'disk' => $this->disk,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'human_size' => $this->humanSize,
            'alt_text' => $this->alt_text,
            'caption' => $this->caption,
            'width' => $this->width,
            'height' => $this->height,
            'url' => $this->url,
            'is_image' => $this->isImage,
            'variants' => $this->when($this->variants, $this->variants),
            'download_url' => $this->downloadUrl,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            // Relationships (when loaded)
            'collections' => MediaCollectionResource::collection($this->whenLoaded('collections')),

            // Counts (when loaded)
            'collections_count' => $this->when(isset($this->collections_count), $this->collections_count),
        ];
    }
}
