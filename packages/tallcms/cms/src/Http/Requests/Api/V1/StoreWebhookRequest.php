<?php

declare(strict_types=1);

namespace TallCms\Cms\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use TallCms\Cms\Models\Webhook;
use TallCms\Cms\Services\WebhookUrlValidator;

class StoreWebhookRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled in route middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'url' => ['required', 'url', 'max:2048'],
            'events' => ['required', 'array', 'min:1'],
            'events.*' => ['string', 'in:*,'.implode(',', Webhook::EVENTS)],
            'is_active' => ['sometimes', 'boolean'],
            'timeout' => ['sometimes', 'integer', 'min:5', 'max:60'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $url = $this->input('url');

            if ($url) {
                $urlValidator = app(WebhookUrlValidator::class);
                $result = $urlValidator->validateOnCreate($url);

                if (! $result['valid']) {
                    $validator->errors()->add('url', $result['error']);
                }
            }
        });
    }
}
