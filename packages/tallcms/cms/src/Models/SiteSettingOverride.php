<?php

declare(strict_types=1);

namespace TallCms\Cms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteSettingOverride extends Model
{
    protected $table = 'tallcms_site_setting_overrides';

    protected $fillable = [
        'site_id',
        'key',
        'value',
        'type',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class, 'site_id');
    }

    /**
     * Cast the stored value based on the type column.
     */
    public function castValue(): mixed
    {
        return match ($this->type) {
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($this->value, true),
            'file' => $this->value,
            default => $this->value,
        };
    }

    /**
     * Static cast helper for raw DB query results.
     */
    public static function castRawValue(string $value, string $type): mixed
    {
        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($value, true),
            'file' => $value,
            default => $value,
        };
    }
}
