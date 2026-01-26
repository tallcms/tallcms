<?php

declare(strict_types=1);

namespace TallCms\Cms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;

class SiteSetting extends Model
{
    protected $table = 'tallcms_site_settings';

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'description',
    ];

    protected $casts = [
        'value' => 'string',
    ];

    /**
     * Get a setting value by key
     *
     * Gracefully handles missing database table (e.g., during migrations or tests).
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $cacheKey = "site_setting_{$key}";

        return Cache::remember($cacheKey, 3600, function () use ($key, $default) {
            try {
                $setting = static::where('key', $key)->first();

                if (! $setting) {
                    return $default;
                }

                return match ($setting->type) {
                    'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
                    'json' => json_decode($setting->value, true),
                    'file' => $setting->value,
                    default => $setting->value,
                };
            } catch (QueryException) {
                // Table doesn't exist yet (migrations not run, testing, etc.)
                return $default;
            }
        });
    }

    /**
     * Set a setting value
     */
    public static function set(string $key, mixed $value, string $type = 'text', string $group = 'general', ?string $description = null): void
    {
        $processedValue = match ($type) {
            'boolean' => $value ? '1' : '0',
            'json' => json_encode($value),
            default => (string) $value,
        };

        static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $processedValue,
                'type' => $type,
                'group' => $group,
                'description' => $description,
            ]
        );

        // Clear cache
        Cache::forget("site_setting_{$key}");
    }

    /**
     * Get all settings for a group
     *
     * Gracefully handles missing database table.
     */
    public static function group(string $group): array
    {
        try {
            $settings = static::where('group', $group)->get();

            $result = [];
            foreach ($settings as $setting) {
                $result[$setting->key] = static::get($setting->key);
            }

            return $result;
        } catch (QueryException) {
            return [];
        }
    }

    /**
     * Clear all settings cache
     *
     * Gracefully handles missing database table.
     */
    public static function clearCache(): void
    {
        try {
            $settings = static::all();
            foreach ($settings as $setting) {
                Cache::forget("site_setting_{$setting->key}");
            }
        } catch (QueryException) {
            // Table doesn't exist yet
        }
    }
}
