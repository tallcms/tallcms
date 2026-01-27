<?php

declare(strict_types=1);

namespace TallCms\Cms\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreMediaRequest extends FormRequest
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
            'file' => ['required', 'file', 'max:102400'], // 100MB max
            'name' => ['sometimes', 'string', 'max:255'],
            'disk' => ['sometimes', 'string', 'in:public,s3,local'],
            'alt_text' => ['sometimes', 'nullable', 'string', 'max:255'],
            'caption' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'collection_ids' => ['sometimes', 'array'],
            'collection_ids.*' => ['integer', 'exists:'.$prefix.'media_collections,id'],
        ];
    }
}
