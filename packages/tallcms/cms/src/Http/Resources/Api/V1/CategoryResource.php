<?php

declare(strict_types=1);

namespace TallCms\Cms\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \TallCms\Cms\Models\CmsCategory
 */
class CategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $locale = $request->input('locale') ?? $request->header('X-Locale');
        $withTranslations = filter_var($request->input('with_translations', false), FILTER_VALIDATE_BOOLEAN);

        return [
            'id' => $this->id,
            'name' => $this->getLocalizedOrAll('name', $locale, $withTranslations),
            'slug' => $this->getLocalizedOrAll('slug', $locale, $withTranslations),
            'description' => $this->getLocalizedOrAll('description', $locale, $withTranslations),
            'color' => $this->color,
            'sort_order' => $this->sort_order,
            'parent_id' => $this->parent_id,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            // Relationships (when loaded)
            'parent' => new CategoryResource($this->whenLoaded('parent')),
            'children' => CategoryResource::collection($this->whenLoaded('children')),

            // Counts (when loaded)
            'posts_count' => $this->when(isset($this->posts_count), $this->posts_count),
            'children_count' => $this->when(isset($this->children_count), $this->children_count),
        ];
    }

    /**
     * Get localized value or all translations based on request.
     */
    protected function getLocalizedOrAll(string $field, ?string $locale, bool $withTranslations): mixed
    {
        if ($withTranslations) {
            return $this->getTranslations($field);
        }

        if ($locale) {
            return $this->getTranslation($field, $locale);
        }

        // Return default locale value
        return $this->$field;
    }
}
