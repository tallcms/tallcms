<?php

declare(strict_types=1);

namespace TallCms\Cms\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMediaCollectionRequest extends FormRequest
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
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255'],

            // Multi-locale mode (translations object)
            'translations' => ['sometimes', 'array'],
            'translations.name' => ['sometimes', 'array'],
            'translations.name.*' => ['string', 'max:255'],
            'translations.slug' => ['sometimes', 'array'],
            'translations.slug.*' => ['string', 'max:255'],

            // Non-translatable fields
            'color' => ['sometimes', 'nullable', 'string', 'max:7'],
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
