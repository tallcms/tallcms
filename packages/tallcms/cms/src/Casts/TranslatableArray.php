<?php

declare(strict_types=1);

namespace TallCms\Cms\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Cast for translatable array fields (like block content).
 *
 * Works with Spatie's HasTranslations to ensure arrays are properly decoded.
 * When Spatie retrieves translated values, it returns them as strings (JSON).
 * This cast ensures the content is properly decoded back to an array.
 */
class TranslatableArray implements CastsAttributes
{
    /**
     * Cast the given value to an array.
     *
     * @param  Model  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @return array|null
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?array
    {
        if ($value === null) {
            return null;
        }

        // Spatie returns the translated value as a string if it's JSON
        if (is_string($value)) {
            $decoded = json_decode($value, true);

            return is_array($decoded) ? $decoded : [];
        }

        return is_array($value) ? $value : [];
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  Model  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @return string|null
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        // Ensure arrays are stored as JSON strings for Spatie
        return is_array($value) ? json_encode($value) : $value;
    }
}
