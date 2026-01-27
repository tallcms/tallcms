<?php

declare(strict_types=1);

namespace TallCms\Cms\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \TallCms\Cms\Models\CmsRevision
 */
class RevisionResource extends JsonResource
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
            'revision_number' => $this->revision_number,
            'is_pinned' => $this->is_pinned,
            'note' => $this->note,
            'content' => $this->content,
            'user_id' => $this->user_id,
            'created_at' => $this->created_at?->toIso8601String(),

            // Relationships (when loaded)
            'user' => new UserResource($this->whenLoaded('user')),
        ];
    }
}
