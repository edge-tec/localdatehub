<?php

namespace App\Support\Storage;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class MediaStorageService
{
    protected string $defaultDisk;

    protected ImageProcessor $processor;

    public function __construct(?ImageProcessor $processor = null)
    {
        $this->defaultDisk = config('filesystems.default', 'local');
        $this->processor = $processor ?? new ImageProcessor();
    }

    /**
     * Validate, normalize EXIF orientation, strip metadata, resize if needed, and atomically store clean WebP.
     *
     * @return array{path: string, url: string, filename: string, disk: string, size: int, width: int, height: int, mime_type: string}
     */
    public function storeSanitizedPhoto(
        UploadedFile $file,
        string $directory = 'photos',
        ?string $disk = null,
        int $maxWidth = 1600,
        int $maxHeight = 1600,
        int $quality = 85
    ): array {
        $targetDisk = $disk ?? $this->defaultDisk;
        $maxSize = (int) config('localdatehub.safety.max_photo_size_kb', 8192) * 1024;
        $realPath = $file->getRealPath();

        if (!$realPath) {
            throw new InvalidArgumentException('Uploaded photo is unreadable.');
        }

        $meta = $this->processor->validateFile(
            $realPath,
            $maxSize,
            6000,
            ImageProcessor::ALLOWED_PHOTO_MIMES
        );

        $image = $this->processor->loadNormalizedImage($realPath, $meta['mime']);
        $resized = $this->processor->resizeKeepingAspect($image, $maxWidth, $maxHeight);
        $cleanBinary = $this->processor->encodeWebpBytes($resized, $quality);

        $filename = Str::uuid()->toString() . '.webp';
        $relativeDir = trim($directory, '/');
        $fullPath = $relativeDir !== '' ? "{$relativeDir}/{$filename}" : $filename;

        $diskInstance = Storage::disk($targetDisk);
        if (config("filesystems.disks.{$targetDisk}.driver") === 'local') {
            $diskPath = $diskInstance->path($fullPath);
            $dir = dirname($diskPath);
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            $tmpPath = $diskPath . '.tmp.' . Str::random(10);
            try {
                file_put_contents($tmpPath, $cleanBinary);
                @chmod($tmpPath, 0644);
                if (!@rename($tmpPath, $diskPath)) {
                    throw new RuntimeException("Failed to commit sanitized photo: {$fullPath}");
                }
            } finally {
                if (is_file($tmpPath)) {
                    @unlink($tmpPath);
                }
            }
        } else {
            $diskInstance->put($fullPath, $cleanBinary);
        }

        return [
            'path' => $fullPath,
            'url' => $diskInstance->url($fullPath),
            'filename' => $filename,
            'disk' => $targetDisk,
            'size' => strlen($cleanBinary),
            'width' => imagesx($resized),
            'height' => imagesy($resized),
            'mime_type' => 'image/webp',
        ];
    }

    /**
     * Delete a stored media item.
     */
    public function deleteFile(string $path, ?string $disk = null): bool
    {
        $targetDisk = $disk ?? $this->defaultDisk;
        return Storage::disk($targetDisk)->delete($path);
    }

    /**
     * Generate public URL for media.
     */
    public function getUrl(string $path, ?string $disk = null): string
    {
        $targetDisk = $disk ?? $this->defaultDisk;
        return Storage::disk($targetDisk)->url($path);
    }
}
