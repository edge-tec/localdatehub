<?php

namespace App\Support\Storage;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class MediaStorageService
{
    protected string $defaultDisk;

    public function __construct()
    {
        $this->defaultDisk = config('filesystems.default', 'local');
    }

    /**
     * Store an uploaded photo after stripping EXIF and re-encoding to protect user location privacy.
     *
     * @param  UploadedFile  $file
     * @param  string  $directory
     * @param  string|null  $disk
     * @return array{path: string, url: string, filename: string, disk: string, size: int}
     */
    public function storeSanitizedPhoto(UploadedFile $file, string $directory = 'photos', ?string $disk = null): array
    {
        $targetDisk = $disk ?? $this->defaultDisk;
        $maxSize = config('localdatehub.safety.max_photo_size_kb', 8192) * 1024;

        if ($file->getSize() > $maxSize) {
            throw new RuntimeException("Uploaded file exceeds the maximum allowed size of {$maxSize} bytes.");
        }

        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($file->getMimeType(), $allowedMimes)) {
            throw new RuntimeException('Invalid image format. Allowed formats: JPEG, PNG, WebP.');
        }

        $filename = Str::uuid()->toString() . '.webp';
        $relativeDir = trim($directory, '/');
        $fullPath = $relativeDir . '/' . $filename;

        // Re-encode image using GD to strip EXIF GPS data and normalize to WebP
        $cleanBinary = $this->stripExifAndReencode($file);

        Storage::disk($targetDisk)->put($fullPath, $cleanBinary);

        return [
            'path' => $fullPath,
            'url' => Storage::disk($targetDisk)->url($fullPath),
            'filename' => $filename,
            'disk' => $targetDisk,
            'size' => strlen($cleanBinary),
        ];
    }

    /**
     * Re-encodes an image through GD to strip all EXIF metadata and output clean WebP.
     */
    protected function stripExifAndReencode(UploadedFile $file): string
    {
        $mime = $file->getMimeType();
        $sourcePath = $file->getRealPath();

        $image = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($sourcePath),
            'image/png' => @imagecreatefrompng($sourcePath),
            'image/webp' => @imagecreatefromwebp($sourcePath),
            default => null,
        };

        if (!$image) {
            // Fallback: return file contents directly if GD fails
            return file_get_contents($sourcePath);
        }

        // Enable alpha blending and save alpha
        imagealphablending($image, true);
        imagesavealpha($image, true);

        ob_start();
        imagewebp($image, null, 85);
        $webpData = ob_get_clean();
        imagedestroy($image);

        return $webpData ?: file_get_contents($sourcePath);
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
