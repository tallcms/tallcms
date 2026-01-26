<?php

declare(strict_types=1);

namespace TallCms\Cms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

class TallcmsMedia extends Model
{
    protected $table = 'tallcms_media';

    protected $fillable = [
        'name',
        'file_name',
        'mime_type',
        'path',
        'disk',
        'size',
        'meta',
        'alt_text',
        'caption',
        'optimized_at',
        'has_variants',
    ];

    protected $casts = [
        'meta' => 'array',
        'size' => 'integer',
        'has_variants' => 'boolean',
        'optimized_at' => 'datetime',
    ];

    /**
     * Get the full URL for the media file
     */
    public function getUrlAttribute(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }

    /**
     * Get human readable file size
     */
    public function getHumanSizeAttribute(): string
    {
        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2).' '.$units[$i];
    }

    /**
     * Check if the file is an image
     */
    public function getIsImageAttribute(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    /**
     * Get image dimensions if available
     */
    public function getDimensionsAttribute(): ?string
    {
        if (! $this->is_image || ! isset($this->meta['width'], $this->meta['height'])) {
            return null;
        }

        return $this->meta['width'].' × '.$this->meta['height'];
    }

    /**
     * Get the collections that this media belongs to
     */
    public function collections(): BelongsToMany
    {
        return $this->belongsToMany(
            MediaCollection::class,
            'tallcms_media_collection_pivot',
            'media_id',
            'collection_id'
        )->withTimestamps();
    }

    /**
     * Scope by collection
     */
    public function scopeInCollection($query, $collection)
    {
        if (is_string($collection)) {
            return $query->whereHas('collections', fn ($q) => $q->where('tallcms_media_collections.slug', $collection));
        }

        if (is_array($collection)) {
            return $query->whereHas('collections', fn ($q) => $q->whereIn('tallcms_media_collections.id', $collection));
        }

        return $query->whereHas('collections', fn ($q) => $q->where('tallcms_media_collections.id', $collection));
    }

    /**
     * Scope by mime type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('mime_type', 'like', $type.'%');
    }

    /**
     * Check if a variant exists for the given size
     */
    public function hasVariant(string $size): bool
    {
        $variants = $this->meta['variants'] ?? [];

        return isset($variants[$size]);
    }

    /**
     * Get the URL for a specific variant, with fallback to original
     */
    public function getVariantUrl(string $size = 'medium'): string
    {
        $variants = $this->meta['variants'] ?? [];

        if (isset($variants[$size])) {
            return Storage::disk($this->disk)->url($variants[$size]);
        }

        // Fallback to original
        return $this->url;
    }

    /**
     * Get image width from meta
     */
    public function getWidthAttribute(): ?int
    {
        return $this->meta['width'] ?? null;
    }

    /**
     * Get image height from meta
     */
    public function getHeightAttribute(): ?int
    {
        return $this->meta['height'] ?? null;
    }

    /**
     * Get the download URL (forces browser download instead of display)
     * Uses signed URL to prevent ID guessing attacks.
     */
    public function getDownloadUrlAttribute(): string
    {
        return \Illuminate\Support\Facades\URL::signedRoute(
            'tallcms.media.download',
            ['media' => $this->id],
            now()->addHours(24)
        );
    }

    /**
     * Delete the physical file and variants when model is deleted
     */
    protected static function booted()
    {
        static::deleting(function (TallcmsMedia $media) {
            // Delete variant files first
            if ($media->has_variants) {
                app(\TallCms\Cms\Services\ImageOptimizer::class)->deleteVariants($media);
            }

            // Delete original file
            if (Storage::disk($media->disk)->exists($media->path)) {
                Storage::disk($media->disk)->delete($media->path);
            }
        });
    }
}
