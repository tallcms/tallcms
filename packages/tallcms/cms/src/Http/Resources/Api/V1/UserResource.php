<?php

declare(strict_types=1);

namespace TallCms\Cms\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'email' => $this->email,
            'author_slug' => $this->author_slug ?? null,
            'author_bio' => $this->author_bio ?? null,
        ];
    }
}
