<?php

declare(strict_types=1);

namespace TallCms\Cms\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \TallCms\Cms\Models\CmsPage
 */
class PageResource extends JsonResource
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
            'title' => $this->getLocalizedOrAll('title', $locale, $withTranslations),
            'slug' => $this->getLocalizedOrAll('slug', $locale, $withTranslations),
            'content' => $this->getLocalizedOrAll('content', $locale, $withTranslations),
            'meta_title' => $this->getLocalizedOrAll('meta_title', $locale, $withTranslations),
            'meta_description' => $this->getLocalizedOrAll('meta_description', $locale, $withTranslations),
            'status' => $this->status instanceof \BackedEnum ? $this->status->value : $this->status,
            'is_homepage' => $this->is_homepage,
            'content_width' => $this->content_width,
            'show_breadcrumbs' => $this->show_breadcrumbs,
            'sort_order' => $this->sort_order,
            'parent_id' => $this->parent_id,
            'author_id' => $this->author_id,
            'published_at' => $this->published_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'deleted_at' => $this->when($this->deleted_at, fn () => $this->deleted_at?->toIso8601String()),

            // Relationships (when loaded)
            'parent' => new PageResource($this->whenLoaded('parent')),
            'children' => PageResource::collection($this->whenLoaded('children')),
            'author' => new UserResource($this->whenLoaded('author')),

            // Counts (when loaded)
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
