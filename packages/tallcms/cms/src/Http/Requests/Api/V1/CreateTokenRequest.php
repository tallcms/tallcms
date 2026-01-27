<?php

declare(strict_types=1);

namespace TallCms\Cms\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use TallCms\Cms\Validation\TokenAbilityValidator;

class CreateTokenRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Public endpoint (anyone can request a token with valid credentials)
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:255'],
            'abilities' => ['required', 'array', 'min:1'],
            'abilities.*' => ['required', 'string', 'in:'.implode(',', TokenAbilityValidator::VALID_ABILITIES)],
            'expires_in_days' => ['sometimes', 'integer', 'min:1', 'max:365'],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'abilities.*.in' => 'The ability ":input" is not valid. Valid abilities: '.implode(', ', TokenAbilityValidator::VALID_ABILITIES),
        ];
    }
}
