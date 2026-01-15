<?php

declare(strict_types=1);

namespace TallCms\Cms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class MediaCollection extends Model
{
    protected $table = 'tallcms_media_collections';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'color',
    ];

    protected static function booted()
    {
        static::creating(function (MediaCollection $collection) {
            if (empty($collection->slug)) {
                $collection->slug = Str::slug($collection->name);
            }
        });

        static::updating(function (MediaCollection $collection) {
            if ($collection->isDirty('name') && empty($collection->slug)) {
                $collection->slug = Str::slug($collection->name);
            }
        });
    }

    public function media(): BelongsToMany
    {
        return $this->belongsToMany(
            TallcmsMedia::class,
            'tallcms_media_collection_pivot',
            'collection_id',
            'media_id'
        )->withTimestamps();
    }

    public function getMediaCountAttribute(): int
    {
        return $this->media()->count();
    }
}
