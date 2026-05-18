<?php

namespace Stemizer\FilamentTinyFinder\Services;

use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Stemizer\FilamentTinyFinder\Models\TinyFinderFile;

class ImageProcessingService
{
    protected ImageManager $manager;

    public function __construct()
    {
        $driver = config('filament-tinyfinder.images.driver') === 'imagick'
            ? new ImagickDriver()
            : new GdDriver();

        $this->manager = new ImageManager($driver);
    }

    /**
     * Create thumbnails for image
     */
    public function createThumbnails(TinyFinderFile $file): bool
    {
        if ($file->type !== 'image') {
            return false;
        }

        $disk = config('filament-tinyfinder.storage.disk');
        $path = config('filament-tinyfinder.storage.path');
        $sourcePath = Storage::disk($disk)->path("{$path}/images/{$file->basename}");

        if (!file_exists($sourcePath)) {
            return false;
        }

        $image = $this->manager->read($sourcePath);
        $sizes = config('filament-tinyfinder.images.thumbnails.sizes');
        $fit = config('filament-tinyfinder.images.thumbnails.fit');

        foreach ($sizes as $sizeName => $dimensions) {
            $thumb = clone $image;

            match ($fit) {
                'contain' => $thumb->scale(
                    width: $dimensions['width'],
                    height: $dimensions['height']
                ),
                'cover' => $thumb->cover(
                    width: $dimensions['width'],
                    height: $dimensions['height']
                ),
                'fill' => $thumb->resize(
                    width: $dimensions['width'],
                    height: $dimensions['height']
                ),
                default => $thumb->scale(
                    width: $dimensions['width'],
                    height: $dimensions['height']
                ),
            };

            $thumbPath = "{$path}/images/thumbs/{$sizeName}_{$file->basename}";
            Storage::disk($disk)->put(
                $thumbPath,
                $this->encodeImage($thumb, $file->extension)
            );
        }

        return true;
    }

    /**
     * Resize image
     */
    public function resize(
        TinyFinderFile $file,
        ?int $width = null,
        ?int $height = null,
        int $type = 2
    ): bool {
        if ($file->type !== 'image') {
            return false;
        }

        if (!$width && !$height) {
            return false;
        }

        $disk = config('filament-tinyfinder.storage.disk');
        $path = config('filament-tinyfinder.storage.path');
        $filePath = Storage::disk($disk)->path("{$path}/images/{$file->basename}");

        if (!file_exists($filePath)) {
            return false;
        }

        $image = $this->manager->read($filePath);

        match ($type) {
            1 => $this->resizeStandard($image, $width, $height), // Standard
            2 => $this->resizeKeepRatio($image, $width, $height), // Keep ratio
            3 => $this->resizeCropCenter($image, $width, $height), // Crop center
            4 => $this->resizeFillBackground($image, $width, $height), // Fill background
            default => $this->resizeKeepRatio($image, $width, $height),
        };

        Storage::disk($disk)->put(
            "{$path}/images/{$file->basename}",
            $this->encodeImage($image, $file->extension)
        );

        // Recreate thumbnails
        if ($file->has_thumbnails) {
            $this->createThumbnails($file);
        }

        return true;
    }

    /**
     * Crop image
     */
    public function crop(
        TinyFinderFile $file,
        int $x,
        int $y,
        int $width,
        int $height
    ): bool {
        if ($file->type !== 'image') {
            return false;
        }

        $disk = config('filament-tinyfinder.storage.disk');
        $path = config('filament-tinyfinder.storage.path');
        $filePath = Storage::disk($disk)->path("{$path}/images/{$file->basename}");

        if (!file_exists($filePath)) {
            return false;
        }

        $image = $this->manager->read($filePath);

        $image->crop($width, $height, $x, $y);

        Storage::disk($disk)->put(
            "{$path}/images/{$file->basename}",
            $this->encodeImage($image, $file->extension)
        );

        // Update dimensions
        $encodedImage = $this->encodeImage($image, $file->extension);

        $file->update([
            'width' => $width,
            'height' => $height,
            'size' => strlen($encodedImage),
        ]);

        // Recreate thumbnails
        if ($file->has_thumbnails) {
            $this->createThumbnails($file);
        }

        return true;
    }

    /**
     * Standard resize
     */
    protected function resizeStandard($image, ?int $width, ?int $height): void
    {
        $image->resize($width, $height);
    }

    /**
     * Resize keeping aspect ratio
     */
    protected function resizeKeepRatio($image, ?int $width, ?int $height): void
    {
        $image->scale($width, $height);
    }

    /**
     * Resize and crop from center
     */
    protected function resizeCropCenter($image, ?int $width, ?int $height): void
    {
        $image->cover($width, $height);
    }

    /**
     * Resize and fill background
     */
    protected function resizeFillBackground($image, ?int $width, ?int $height): void
    {
        $image->pad($width, $height, 'ffffff');
    }

    /**
     * Get image dimensions
     */
    public function getDimensions(TinyFinderFile $file): ?array
    {
        if ($file->type !== 'image') {
            return null;
        }

        $disk = config('filament-tinyfinder.storage.disk');
        $path = config('filament-tinyfinder.storage.path');
        $filePath = Storage::disk($disk)->path("{$path}/images/{$file->basename}");

        if (!file_exists($filePath)) {
            return null;
        }

        $image = $this->manager->read($filePath);

        return [
            'width' => $image->width(),
            'height' => $image->height(),
        ];
    }

    /**
     * Rotate image
     */
    public function rotate(TinyFinderFile $file, int $degrees): bool
    {
        if ($file->type !== 'image') {
            return false;
        }

        $disk = config('filament-tinyfinder.storage.disk');
        $path = config('filament-tinyfinder.storage.path');
        $filePath = Storage::disk($disk)->path("{$path}/images/{$file->basename}");

        if (!file_exists($filePath)) {
            return false;
        }

        $image = $this->manager->read($filePath);
        $image->rotate($degrees);

        Storage::disk($disk)->put(
            "{$path}/images/{$file->basename}",
            $this->encodeImage($image, $file->extension)
        );

        // Update dimensions
        $file->update([
            'width' => $image->width(),
            'height' => $image->height(),
        ]);

        // Recreate thumbnails
        if ($file->has_thumbnails) {
            $this->createThumbnails($file);
        }

        return true;
    }

    protected function encodeImage($image, ?string $extension = null): string
    {
        $quality = (int) config('filament-tinyfinder.images.quality', 90);

        return (string) match (strtolower((string) $extension)) {
            'jpg', 'jpeg' => $image->toJpeg(quality: $quality),
            'webp' => $image->toWebp(quality: $quality),
            'png' => $image->toPng(),
            'gif' => $image->toGif(),
            'bmp' => $image->toBitmap(),
            default => $image->encodeByExtension($extension),
        };
    }
}
