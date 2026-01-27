<?php

declare(strict_types=1);

namespace TallCms\Cms\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use TallCms\Cms\Enums\ContentStatus;

class StorePostRequest extends FormRequest
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
        $prefix = config('tallcms.database.prefix', 'tallcms_');

        return [
            // Single-locale mode fields
            'title' => ['required_without:translations', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255'],
            'excerpt' => ['sometimes', 'nullable', 'string', 'max:500'],
            'content' => ['sometimes', 'array'],
            'meta_title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'meta_description' => ['sometimes', 'nullable', 'string', 'max:500'],

            // Multi-locale mode (translations object)
            'translations' => ['sometimes', 'array'],
            'translations.title' => ['sometimes', 'array'],
            'translations.title.*' => ['string', 'max:255'],
            'translations.slug' => ['sometimes', 'array'],
            'translations.slug.*' => ['string', 'max:255'],
            'translations.excerpt' => ['sometimes', 'array'],
            'translations.excerpt.*' => ['nullable', 'string', 'max:500'],
            'translations.content' => ['sometimes', 'array'],
            'translations.content.*' => ['array'],
            'translations.meta_title' => ['sometimes', 'array'],
            'translations.meta_title.*' => ['nullable', 'string', 'max:255'],
            'translations.meta_description' => ['sometimes', 'array'],
            'translations.meta_description.*' => ['nullable', 'string', 'max:500'],

            // Non-translatable fields
            'status' => ['sometimes', 'string', 'in:'.implode(',', array_column(ContentStatus::cases(), 'value'))],
            'is_featured' => ['sometimes', 'boolean'],
            'featured_image' => ['sometimes', 'nullable', 'string', 'max:255'],
            'category_ids' => ['sometimes', 'array'],
            'category_ids.*' => ['integer', 'exists:'.$prefix.'categories,id'],
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
