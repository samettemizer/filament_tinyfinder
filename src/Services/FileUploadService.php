<?php

namespace Stemizer\FilamentTinyFinder\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Stemizer\FilamentTinyFinder\Models\TinyFinderFile;

class FileUploadService
{
    public function __construct(
        protected ImageProcessingService $imageService
    ) {}

    /**
     * Upload file
     */
    public function upload(
        UploadedFile $file,
        array $options = []
    ): TinyFinderFile {
        $this->validateFile($file);

        $type = $this->determineType($file);
        $basename = $this->generateBasename($file);
        $disk = config('filament-tinyfinder.storage.disk');
        $path = config('filament-tinyfinder.storage.path');

        // Store file
        $storagePath = "{$path}/{$type}s/{$basename}";
        Storage::disk($disk)->put($storagePath, file_get_contents($file->getRealPath()));

        // Create database record
        $fileRecord = TinyFinderFile::create([
            'user_id' => auth()->id(),
            'type' => $type,
            'name' => $file->getClientOriginalName(),
            'basename' => $basename,
            'extension' => $file->getClientOriginalExtension(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'is_private' => $options['is_private'] ?? false,
            'metadata' => $options['metadata'] ?? null,
        ]);

        // Process image if applicable
        if ($type === 'image') {
            $this->processImage($fileRecord, $options);
        }

        return $fileRecord->fresh();
    }

    /**
     * Validate uploaded file
     */
    protected function validateFile(UploadedFile $file): void
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $type = $this->determineType($file);

        // Check extension
        $allowedExtensions = $type === 'image'
            ? config('filament-tinyfinder.uploads.allowed_image_extensions')
            : config('filament-tinyfinder.uploads.allowed_file_extensions');

        if (!in_array($extension, $allowedExtensions)) {
            throw new \InvalidArgumentException(
                "File extension '{$extension}' is not allowed."
            );
        }

        // Check size
        $maxSize = $type === 'image'
            ? config('filament-tinyfinder.uploads.max_image_size')
            : config('filament-tinyfinder.uploads.max_file_size');

        if ($file->getSize() > $maxSize) {
            $maxSizeMB = round($maxSize / 1024 / 1024, 2);
            throw new \InvalidArgumentException(
                "File size exceeds maximum allowed size of {$maxSizeMB}MB."
            );
        }
    }

    /**
     * Determine file type
     */
    protected function determineType(UploadedFile $file): string
    {
        $imageExtensions = config('filament-tinyfinder.uploads.allowed_image_extensions');
        $extension = strtolower($file->getClientOriginalExtension());

        return in_array($extension, $imageExtensions) ? 'image' : 'file';
    }

    /**
     * Generate unique basename
     */
    protected function generateBasename(UploadedFile $file): string
    {
        return Str::random(8) . '.' . $file->getClientOriginalExtension();
    }

    /**
     * Process image (dimensions, thumbnails, resize)
     */
    protected function processImage(TinyFinderFile $fileRecord, array $options): void
    {
        $disk = config('filament-tinyfinder.storage.disk');
        $path = config('filament-tinyfinder.storage.path');
        $filePath = Storage::disk($disk)->path("{$path}/images/{$fileRecord->basename}");

        // Get dimensions
        [$width, $height] = getimagesize($filePath);
        $fileRecord->update(['width' => $width, 'height' => $height]);

        // Resize if requested
        if (!empty($options['resize'])) {
            $resized = $this->imageService->resize(
                $fileRecord,
                $options['resize']['width'] ?? null,
                $options['resize']['height'] ?? null,
                $options['resize']['type'] ?? 2
            );

            if ($resized) {
                [$width, $height] = getimagesize($filePath);
                $fileRecord->update([
                    'width' => $width,
                    'height' => $height,
                    'size' => filesize($filePath),
                ]);
            }
        }

        // Create thumbnails
        if ($options['create_thumbnails'] ?? config('filament-tinyfinder.images.thumbnails.enabled')) {
            $this->imageService->createThumbnails($fileRecord);
            $fileRecord->update(['has_thumbnails' => true]);
        }
    }

    /**
     * Upload from URL
     */
    public function uploadFromUrl(string $url, array $options = []): TinyFinderFile
    {
        $contents = file_get_contents($url);
        $extension = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION);

        if (!$extension) {
            $extension = 'jpg'; // Default
        }

        $tempFile = tmpfile();
        $tempPath = stream_get_meta_data($tempFile)['uri'];
        file_put_contents($tempPath, $contents);

        $uploadedFile = new UploadedFile(
            $tempPath,
            basename($url),
            mime_content_type($tempPath),
            null,
            true
        );

        $result = $this->upload($uploadedFile, $options);

        fclose($tempFile);

        return $result;
    }

    /**
     * Upload multiple files
     */
    public function uploadMultiple(array $files, array $options = []): array
    {
        $results = [];

        foreach ($files as $file) {
            try {
                $results[] = $this->upload($file, $options);
            } catch (\Exception $e) {
                $results[] = [
                    'error' => true,
                    'message' => $e->getMessage(),
                    'file' => $file->getClientOriginalName(),
                ];
            }
        }

        return $results;
    }
}
