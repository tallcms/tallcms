<?php

declare(strict_types=1);

namespace TallCms\Cms\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\ImageManager;
use TallCms\Cms\Models\TallcmsMedia;

class ImageOptimizer
{
    protected ImageManager $manager;

    public function __construct()
    {
        // Prefer Imagick if available, fallback to GD
        $this->manager = extension_loaded('imagick')
            ? new ImageManager(new ImagickDriver())
            : new ImageManager(new GdDriver());
    }

    /**
     * Generate WebP variants for a media item.
     */
    public function generateVariants(TallcmsMedia $media): void
    {
        if (! $media->is_image) {
            return;
        }

        // Skip if already optimized
        if ($media->has_variants) {
            return;
        }

        // Skip non-image formats that can't be processed
        $supportedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (! in_array($media->mime_type, $supportedTypes)) {
            return;
        }

        $disk = $media->disk;
        $originalPath = $media->path;

        $tempPath = null;
        $variantTempPaths = [];

        try {
            // Download to temp file for processing
            $tempPath = $this->processImage($originalPath, $disk);

            // Get variant presets from config
            $variants = config('tallcms.media.optimization.variants', [
                'thumbnail' => ['width' => 300, 'height' => 300, 'fit' => 'crop'],
                'medium' => ['width' => 800, 'height' => 600, 'fit' => 'contain'],
                'large' => ['width' => 1200, 'height' => 800, 'fit' => 'contain'],
            ]);

            $variantPaths = [];
            $baseDir = pathinfo($originalPath, PATHINFO_DIRNAME);
            $baseName = pathinfo($originalPath, PATHINFO_FILENAME);

            // Extract dimensions from original for meta update
            $image = $this->manager->read($tempPath);
            $width = $image->width();
            $height = $image->height();

            foreach ($variants as $size => $dimensions) {
                $variantFilename = "{$baseName}-{$size}.webp";
                $variantPath = $baseDir.'/'.$variantFilename;

                // Create the variant
                $image = $this->manager->read($tempPath);

                if (($dimensions['fit'] ?? 'contain') === 'crop') {
                    $image->cover($dimensions['width'], $dimensions['height']);
                } else {
                    $image->scaleDown($dimensions['width'], $dimensions['height']);
                }

                // Encode as WebP with quality setting
                $quality = config('tallcms.media.optimization.quality', 80);
                $encoded = $image->toWebp($quality);

                // Save variant to temp file
                $variantTempPath = sys_get_temp_dir().'/'.uniqid('variant_').'_'.$variantFilename;
                $variantTempPaths[] = $variantTempPath;
                file_put_contents($variantTempPath, $encoded->toString());

                // Upload to storage
                $this->uploadVariant($variantTempPath, $variantPath, $disk);

                $variantPaths[$size] = $variantPath;
            }

            // Update media record with variant paths and dimensions
            $meta = $media->meta ?? [];
            $meta['variants'] = $variantPaths;
            $meta['width'] = $width;
            $meta['height'] = $height;
            $media->update([
                'meta' => $meta,
                'has_variants' => true,
                'optimized_at' => now(),
            ]);

            Log::info('Media variants generated', [
                'media_id' => $media->id,
                'variants' => array_keys($variantPaths),
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to generate media variants', [
                'media_id' => $media->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        } finally {
            // Always clean up temp files
            if ($tempPath) {
                @unlink($tempPath);
            }
            foreach ($variantTempPaths as $path) {
                @unlink($path);
            }
        }
    }

    /**
     * Delete all variant files for a media item.
     */
    public function deleteVariants(TallcmsMedia $media): void
    {
        if (! $media->has_variants) {
            return;
        }

        $variants = $media->meta['variants'] ?? [];
        $disk = $media->disk;

        foreach ($variants as $size => $path) {
            try {
                if (Storage::disk($disk)->exists($path)) {
                    Storage::disk($disk)->delete($path);
                }
            } catch (\Throwable $e) {
                Log::warning('Failed to delete media variant', [
                    'media_id' => $media->id,
                    'variant' => $size,
                    'path' => $path,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Download file from storage to local temp for processing.
     */
    protected function processImage(string $path, string $disk): string
    {
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $tempPath = sys_get_temp_dir().'/'.uniqid('media_').'_'.basename($path);

        // Download from any disk to local temp
        $contents = Storage::disk($disk)->get($path);
        file_put_contents($tempPath, $contents);

        return $tempPath;
    }

    /**
     * Upload processed variant back to storage.
     */
    protected function uploadVariant(string $localPath, string $targetPath, string $disk): void
    {
        $contents = file_get_contents($localPath);
        Storage::disk($disk)->put($targetPath, $contents, cms_media_visibility());
    }
}
