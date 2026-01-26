<?php

namespace TallCms\Cms\Filament\Resources\TallcmsMedia\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use TallCms\Cms\Filament\Resources\TallcmsMedia\TallcmsMediaResource;
use TallCms\Cms\Jobs\OptimizeMediaJob;
use TallCms\Cms\Models\TallcmsMedia;

class CreateTallcmsMedia extends CreateRecord
{
    protected static string $resource = TallcmsMediaResource::class;

    protected function handleRecordCreation(array $data): TallcmsMedia
    {
        // Handle multiple file uploads
        $uploadedFiles = $data['upload'] ?? [];
        unset($data['upload']);

        // Get original filenames stored by FileUpload
        $originalNames = $data['original_names'] ?? [];
        unset($data['original_names']);

        // Extract collection IDs if set (using collection_ids field for bulk upload)
        $collectionIds = $data['collection_ids'] ?? [];
        unset($data['collection_ids']);

        if (empty($uploadedFiles)) {
            return TallcmsMedia::create($data);
        }

        // Process first file for single upload, or handle multiple files
        $files = is_array($uploadedFiles) ? $uploadedFiles : [$uploadedFiles];
        $createdRecords = [];

        $disk = \cms_media_disk();

        foreach ($files as $filePath) {
            // Get original filename from stored names, fallback to path basename
            $originalName = $originalNames[$filePath] ?? pathinfo($filePath, PATHINFO_BASENAME);
            $mimeType = Storage::disk($disk)->mimeType($filePath);
            $size = Storage::disk($disk)->size($filePath);
            $isImage = str_starts_with($mimeType, 'image/');

            // Get image dimensions if it's an image
            $meta = [];
            if ($isImage) {
                if ($disk === 'public') {
                    $fullPath = Storage::disk($disk)->path($filePath);
                    if (file_exists($fullPath)) {
                        [$width, $height] = @getimagesize($fullPath);
                        if ($width && $height) {
                            $meta = ['width' => $width, 'height' => $height];
                        }
                    }
                } else {
                    // For remote disks, download to temp to get dimensions
                    $tempPath = sys_get_temp_dir().'/'.uniqid('media_dim_').'.'.pathinfo($filePath, PATHINFO_EXTENSION);
                    try {
                        file_put_contents($tempPath, Storage::disk($disk)->get($filePath));
                        [$width, $height] = @getimagesize($tempPath);
                        if ($width && $height) {
                            $meta = ['width' => $width, 'height' => $height];
                        }
                    } finally {
                        @unlink($tempPath);
                    }
                }
            }

            // Auto-generate alt text from filename if not provided and is an image
            $altText = $data['alt_text'] ?? null;
            if (empty($altText) && $isImage) {
                $altText = Str::headline(pathinfo($originalName, PATHINFO_FILENAME));
            }

            $record = TallcmsMedia::create(array_merge($data, [
                'name' => $originalName,
                'file_name' => $originalName,
                'mime_type' => $mimeType,
                'path' => $filePath,
                'disk' => $disk,
                'size' => $size,
                'meta' => $meta,
                'alt_text' => $altText,
            ]));

            // Attach to collections if specified
            if (! empty($collectionIds)) {
                $record->collections()->attach($collectionIds);
            }

            // Dispatch optimization job for images
            if (config('tallcms.media.optimization.enabled', true) && $isImage) {
                OptimizeMediaJob::dispatch($record)
                    ->onQueue(config('tallcms.media.optimization.queue', 'default'));
            }

            $createdRecords[] = $record;
        }

        // Return the first created record
        return $createdRecords[0];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
