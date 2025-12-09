<?php

namespace App\Filament\Resources\TallcmsMedia\Pages;

use App\Filament\Resources\TallcmsMedia\TallcmsMediaResource;
use App\Models\TallcmsMedia;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class EditTallcmsMedia extends EditRecord
{
    protected static string $resource = TallcmsMediaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->modalHeading('Delete Media File')
                ->modalDescription('Are you sure you want to delete this media file? This action cannot be undone and the file will be permanently removed from storage.')
                ->modalSubmitActionLabel('Delete File'),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // Handle file replacement
        if (!empty($data['new_file'])) {
            $newFilePath = $data['new_file'];
            $oldPath = $record->path;
            
            // Delete the old file if it exists
            if ($oldPath && Storage::disk($record->disk)->exists($oldPath)) {
                Storage::disk($record->disk)->delete($oldPath);
            }
            
            // Get new file info
            $fullPath = Storage::disk('public')->path($newFilePath);
            $originalName = pathinfo($newFilePath, PATHINFO_BASENAME);
            $mimeType = Storage::disk('public')->mimeType($newFilePath);
            $size = Storage::disk('public')->size($newFilePath);
            
            // Get image dimensions if it's an image
            $meta = $record->meta ?? [];
            if (str_starts_with($mimeType, 'image/') && file_exists($fullPath)) {
                [$width, $height] = getimagesize($fullPath);
                $meta = array_merge($meta, ['width' => $width, 'height' => $height]);
            }
            
            // Update file metadata
            $data = array_merge($data, [
                'file_name' => $originalName,
                'mime_type' => $mimeType,
                'path' => $newFilePath,
                'size' => $size,
                'meta' => $meta,
            ]);
        }
        
        // Remove new_file from data as it's not a model field
        unset($data['new_file']);
        
        $record->update($data);
        
        return $record;
    }
}
