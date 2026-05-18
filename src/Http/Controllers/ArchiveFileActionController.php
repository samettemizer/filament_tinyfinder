<?php

namespace Stemizer\FilamentTinyFinder\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Stemizer\FilamentTinyFinder\Models\TinyFinderFile;
use Stemizer\FilamentTinyFinder\Services\ImageProcessingService;

class ArchiveFileActionController extends Controller
{
    public function rename(Request $request, TinyFinderFile $file): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $file->update(['name' => $data['name']]);

        return response()->json([
            'id' => $file->getKey(),
            'name' => $file->name,
        ]);
    }

    public function resize(Request $request, TinyFinderFile $file, ImageProcessingService $imageService): JsonResponse
    {
        abort_unless($file->type === 'image', 422);

        $data = $request->validate([
            'width' => ['nullable', 'integer', 'min:1'],
            'height' => ['nullable', 'integer', 'min:1'],
            'type' => ['nullable', 'integer', 'min:1', 'max:4'],
        ]);

        abort_unless($data['width'] ?? $data['height'] ?? null, 422);

        $resized = $imageService->resize(
            $file,
            $data['width'] ?? null,
            $data['height'] ?? null,
            $data['type'] ?? 2,
        );

        abort_unless($resized, 422);

        $dimensions = $imageService->getDimensions($file) ?? [];

        $file->update([
            'width' => $dimensions['width'] ?? $file->width,
            'height' => $dimensions['height'] ?? $file->height,
        ]);

        return response()->json([
            'id' => $file->getKey(),
            'width' => $file->width,
            'height' => $file->height,
        ]);
    }

    public function crop(Request $request, TinyFinderFile $file, ImageProcessingService $imageService): JsonResponse
    {
        abort_unless($file->type === 'image', 422);

        $data = $request->validate([
            'x' => ['required', 'integer', 'min:0'],
            'y' => ['required', 'integer', 'min:0'],
            'width' => ['required', 'integer', 'min:1'],
            'height' => ['required', 'integer', 'min:1'],
        ]);

        $cropped = $imageService->crop(
            $file,
            $data['x'],
            $data['y'],
            $data['width'],
            $data['height'],
        );

        abort_unless($cropped, 422);

        return response()->json([
            'id' => $file->getKey(),
            'width' => $file->width,
            'height' => $file->height,
            'url' => $file->url,
            'thumbnail_url' => $file->thumbnail_url,
        ]);
    }

    public function destroy(TinyFinderFile $file): JsonResponse
    {
        $file->delete();

        return response()->json(['deleted' => true]);
    }
}
