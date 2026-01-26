<?php

declare(strict_types=1);

namespace TallCms\Cms\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use TallCms\Cms\Models\TallcmsMedia;

class MediaDownloadController extends Controller
{
    public function __invoke(Request $request, TallcmsMedia $media): StreamedResponse
    {
        // Validate signed URL to prevent ID guessing attacks
        if (! $request->hasValidSignature()) {
            abort(403, 'Invalid or expired download link');
        }

        $disk = Storage::disk($media->disk);

        if (! $disk->exists($media->path)) {
            abort(404, 'File not found');
        }

        $filename = $media->file_name ?? $media->name ?? basename($media->path);

        return $disk->download($media->path, $filename, [
            'Content-Type' => $media->mime_type,
        ]);
    }
}
