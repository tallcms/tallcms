<?php

declare(strict_types=1);

namespace TallCms\Cms\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use TallCms\Cms\Enums\ContentStatus;

class StorePageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled in controller
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            // Single-locale mode fields
            'title' => ['required_without:translations', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255'],
            'content' => ['sometimes', 'array'],
            'meta_title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'meta_description' => ['sometimes', 'nullable', 'string', 'max:500'],

            // Multi-locale mode (translations object)
            'translations' => ['sometimes', 'array'],
            'translations.title' => ['sometimes', 'array'],
            'translations.title.*' => ['string', 'max:255'],
            'translations.slug' => ['sometimes', 'array'],
            'translations.slug.*' => ['string', 'max:255'],
            'translations.content' => ['sometimes', 'array'],
            'translations.content.*' => ['array'],
            'translations.meta_title' => ['sometimes', 'array'],
            'translations.meta_title.*' => ['nullable', 'string', 'max:255'],
            'translations.meta_description' => ['sometimes', 'array'],
            'translations.meta_description.*' => ['nullable', 'string', 'max:500'],

            // Non-translatable fields
            'status' => ['sometimes', 'string', 'in:'.implode(',', array_column(ContentStatus::cases(), 'value'))],
            'parent_id' => ['sometimes', 'nullable', 'integer', 'exists:'.config('tallcms.database.prefix', 'tallcms_').'pages,id'],
            'is_homepage' => ['sometimes', 'boolean'],
            'content_width' => ['sometimes', 'string', 'in:narrow,standard,wide'],
            'show_breadcrumbs' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $hasLocaleParam = $this->query('locale') || $this->header('X-Locale');
            $hasTranslations = $this->has('translations');

            if ($hasLocaleParam && $hasTranslations) {
                $validator->errors()->add(
                    'translations',
                    'Cannot use both locale parameter and translations object. Choose one mode.'
                );
            }
        });
    }
}
