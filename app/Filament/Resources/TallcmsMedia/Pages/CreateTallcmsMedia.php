<?php

namespace App\Filament\Resources\TallcmsMedia\Pages;

use App\Filament\Resources\TallcmsMedia\TallcmsMediaResource;
use App\Models\TallcmsMedia;
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

        foreach ($files as $filePath) {
            $fullPath = Storage::disk('public')->path($filePath);
            $originalName = pathinfo($filePath, PATHINFO_BASENAME);
            $mimeType = Storage::disk('public')->mimeType($filePath);
            $size = Storage::disk('public')->size($filePath);

            // Get image dimensions if it's an image
            $meta = [];
            if (str_starts_with($mimeType, 'image/') && file_exists($fullPath)) {
                [$width, $height] = getimagesize($fullPath);
                $meta = ['width' => $width, 'height' => $height];
            }

            $record = TallcmsMedia::create(array_merge($data, [
                'name' => $originalName,
                'file_name' => $originalName,
                'mime_type' => $mimeType,
                'path' => $filePath,
                'disk' => 'public',
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
