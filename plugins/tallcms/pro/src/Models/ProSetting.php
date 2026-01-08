<?php

namespace Tallcms\Pro\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class ProSetting extends Model
{
    protected $table = 'tallcms_pro_settings';

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'is_encrypted',
    ];

    protected $casts = [
        'is_encrypted' => 'boolean',
    ];

    /**
     * Cache key prefix for settings
     */
    protected static string $cachePrefix = 'tallcms_pro_setting_';

    /**
     * Get a setting value by key
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember(
            static::$cachePrefix.$key,
            3600,
            function () use ($key, $default) {
                $setting = static::where('key', $key)->first();

                if (! $setting) {
                    return $default;
                }

                return $setting->getValue();
            }
        );
    }

    /**
     * Set a setting value
     */
    public static function set(string $key, mixed $value, string $type = 'text', string $group = 'general', bool $encrypt = false): void
    {
        $storedValue = $value;

        if ($encrypt && $value !== null) {
            $storedValue = encrypt($value);
        } elseif ($type === 'json' && is_array($value)) {
            $storedValue = json_encode($value);
        } elseif ($type === 'boolean') {
            $storedValue = $value ? '1' : '0';
        }

        static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $storedValue,
                'type' => $type,
                'group' => $group,
                'is_encrypted' => $encrypt,
            ]
        );

        Cache::forget(static::$cachePrefix.$key);
    }

    /**
     * Get the decrypted/parsed value
     */
    public function getValue(): mixed
    {
        if ($this->value === null) {
            return null;
        }

        if ($this->is_encrypted) {
            try {
                return decrypt($this->value);
            } catch (\Exception $e) {
                return null;
            }
        }

        return match ($this->type) {
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($this->value, true),
            'integer' => (int) $this->value,
            'float' => (float) $this->value,
            default => $this->value,
        };
    }

    /**
     * Get all settings in a group
     */
    public static function getGroup(string $group): array
    {
        return static::where('group', $group)
            ->get()
            ->mapWithKeys(fn ($setting) => [$setting->key => $setting->getValue()])
            ->toArray();
    }

    /**
     * Clear the cache for a setting
     */
    public static function clearCache(string $key): void
    {
        Cache::forget(static::$cachePrefix.$key);
    }

    /**
     * Clear all settings cache
     */
    public static function clearAllCache(): void
    {
        static::all()->each(function ($setting) {
            Cache::forget(static::$cachePrefix.$setting->key);
        });
    }
}
