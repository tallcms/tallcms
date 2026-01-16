<?php

namespace TallCms\Cms\Filament\Resources\TallcmsMedia\Pages;

use TallCms\Cms\Filament\Resources\TallcmsMedia\TallcmsMediaResource;
use TallCms\Cms\Models\TallcmsMedia;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;

class CreateTallcmsMedia extends CreateRecord
{
    protected static string $resource = TallcmsMediaResource::class;

    protected function handleRecordCreation(array $data): TallcmsMedia
    {
        // Handle multiple file uploads
        $uploadedFiles = $data['upload'] ?? [];
        unset($data['upload']);

        if (empty($uploadedFiles)) {
            return TallcmsMedia::create($data);
        }

        // Process first file for single upload, or handle multiple files
        $files = is_array($uploadedFiles) ? $uploadedFiles : [$uploadedFiles];
        $createdRecords = [];

        $disk = cms_media_disk();

        foreach ($files as $filePath) {
            $originalName = pathinfo($filePath, PATHINFO_BASENAME);
            $mimeType = Storage::disk($disk)->mimeType($filePath);
            $size = Storage::disk($disk)->size($filePath);

            // Get image dimensions if it's an image
            // Note: For S3, we can't use getimagesize directly, so we skip dimensions for remote storage
            $meta = [];
            if (str_starts_with($mimeType, 'image/') && $disk === 'public') {
                $fullPath = Storage::disk($disk)->path($filePath);
                if (file_exists($fullPath)) {
                    [$width, $height] = getimagesize($fullPath);
                    $meta = ['width' => $width, 'height' => $height];
                }
            }

            $record = TallcmsMedia::create(array_merge($data, [
                'name' => $originalName,
                'file_name' => $originalName,
                'mime_type' => $mimeType,
                'path' => $filePath,
                'disk' => $disk,
                'size' => $size,
                'meta' => $meta,
            ]));

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
