<?php

namespace App\Support\Storage;

use GdImage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

/**
 * Reliable GD-based image validator, EXIF-orientation normalizer,
 * true-color converter, and atomic WebP writer.
 */
class ImageProcessor
{
    public const ALLOWED_PHOTO_MIMES = ['image/jpeg', 'image/png', 'image/webp'];

    /**
     * Validate raw image bytes using content inspection (never trusts filename extension).
     *
     * @param  array<int, string>  $allowedMimes
     * @return array{width: int, height: int, mime: string, size: int}
     *
     * @throws InvalidArgumentException
     */
    public function validateFile(
        string $realPath,
        int $maxSizeBytes = 5242880,
        int $maxDimension = 6000,
        array $allowedMimes = self::ALLOWED_PHOTO_MIMES
    ): array {
        if ($realPath === '' || !is_file($realPath) || !is_readable($realPath)) {
            throw new InvalidArgumentException('Uploaded image file is unreadable or missing.');
        }

        $size = @filesize($realPath);
        if ($size === false || $size <= 0) {
            throw new InvalidArgumentException('Uploaded image file is empty or truncated.');
        }

        if ($size > $maxSizeBytes) {
            throw new InvalidArgumentException("Uploaded file exceeds the maximum allowed size of {$maxSizeBytes} bytes.");
        }

        $imageInfo = @getimagesize($realPath);
        if ($imageInfo === false || empty($imageInfo[0]) || empty($imageInfo[1])) {
            throw new InvalidArgumentException('The uploaded file is not a valid or recognizable image format.');
        }

        $actualMime = (string) ($imageInfo['mime'] ?? '');
        if (!in_array($actualMime, $allowedMimes, true)) {
            throw new InvalidArgumentException('Invalid image format. Permitted formats: JPEG, PNG, and WebP.');
        }

        if ($imageInfo[0] > $maxDimension || $imageInfo[1] > $maxDimension) {
            throw new InvalidArgumentException("Image resolution exceeds the maximum allowed dimension of {$maxDimension}x{$maxDimension} pixels.");
        }

        return [
            'width' => (int) $imageInfo[0],
            'height' => (int) $imageInfo[1],
            'mime' => $actualMime,
            'size' => (int) $size,
        ];
    }

    /**
     * Decode an image cleanly, apply EXIF orientation for JPEGs, and normalize to true-color RGBA.
     *
     * @throws RuntimeException
     */
    public function loadNormalizedImage(string $realPath, string $mime): GdImage
    {
        $resource = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($realPath),
            'image/png' => @imagecreatefrompng($realPath),
            'image/webp' => @imagecreatefromwebp($realPath),
            default => false,
        };

        if (!$resource instanceof GdImage) {
            throw new RuntimeException('Failed to decode uploaded image. The file may be corrupted.');
        }

        if ($mime === 'image/jpeg') {
            $resource = $this->applyExifOrientation($resource, $realPath);
        }

        if (!imageistruecolor($resource) && function_exists('imagepalettetotruecolor')) {
            @imagepalettetotruecolor($resource);
        }

        imagealphablending($resource, true);
        imagesavealpha($resource, true);

        return $resource;
    }

    /**
     * Resize preserving aspect ratio into a clean alpha-initialized true-color canvas.
     */
    public function resizeKeepingAspect(GdImage $src, int $maxW, int $maxH): GdImage
    {
        $srcW = imagesx($src);
        $srcH = imagesy($src);

        if ($srcW <= $maxW && $srcH <= $maxH) {
            $targetW = $srcW;
            $targetH = $srcH;
        } else {
            $ratio = min($maxW / $srcW, $maxH / $srcH);
            $targetW = max(1, (int) round($srcW * $ratio));
            $targetH = max(1, (int) round($srcH * $ratio));
        }

        $dst = imagecreatetruecolor($targetW, $targetH);
        if (!$dst instanceof GdImage) {
            throw new RuntimeException('Unable to allocate target image buffer.');
        }

        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
        imagefilledrectangle($dst, 0, 0, $targetW - 1, $targetH - 1, $transparent);

        imagecopyresampled($dst, $src, 0, 0, 0, 0, $targetW, $targetH, $srcW, $srcH);

        return $dst;
    }

    /**
     * Atomically encode and verify a WebP file on disk via a temporary staging file + rename().
     *
     * @throws RuntimeException
     */
    public function writeWebpAtomic(GdImage $image, string $targetDiskPath, int $quality = 85): int
    {
        $dir = dirname($targetDiskPath);
        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new RuntimeException("Unable to create image destination directory: {$dir}");
        }

        $tmpPath = $targetDiskPath . '.tmp.' . Str::random(10);

        try {
            $encoded = @imagewebp($image, $tmpPath, $quality);
            clearstatcache(true, $tmpPath);

            if (!$encoded || !is_file($tmpPath)) {
                throw new RuntimeException('GD WebP encoding failed.');
            }

            $size = @filesize($tmpPath);
            $verify = @getimagesize($tmpPath);
            if ($size === false || $size <= 0 || $verify === false || ($verify['mime'] ?? '') !== 'image/webp') {
                throw new RuntimeException('Generated WebP failed post-write integrity verification.');
            }

            @chmod($tmpPath, 0644);
            if (!@rename($tmpPath, $targetDiskPath)) {
                throw new RuntimeException("Failed to atomically move image into place: {$targetDiskPath}");
            }

            return (int) $size;
        } finally {
            if (is_file($tmpPath)) {
                @unlink($tmpPath);
            }
        }
    }

    /**
     * Encode an image to verified WebP binary bytes in isolation (for Storage::disk()->put()).
     *
     * @throws RuntimeException
     */
    public function encodeWebpBytes(GdImage $image, int $quality = 85): string
    {
        $tmpPath = tempnam(sys_get_temp_dir(), 'ldh_webp_');
        if ($tmpPath === false) {
            throw new RuntimeException('Unable to allocate temporary image file.');
        }

        try {
            $encoded = @imagewebp($image, $tmpPath, $quality);
            clearstatcache(true, $tmpPath);

            $bytes = ($encoded && is_file($tmpPath)) ? @file_get_contents($tmpPath) : false;
            $verify = @getimagesize($tmpPath);

            if ($bytes === false || $bytes === '' || $verify === false || ($verify['mime'] ?? '') !== 'image/webp') {
                throw new RuntimeException('WebP encoding produced empty or invalid output.');
            }

            return $bytes;
        } finally {
            if (is_file($tmpPath)) {
                @unlink($tmpPath);
            }
        }
    }

    /**
     * Verify an existing file on disk is readable, decodable by GD, and matches its extension/MIME.
     *
     * @param  array<int, string>  $allowedMimes
     */
    public function isHealthyDiskImage(string $realPath, array $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif']): bool
    {
        if (!is_file($realPath) || !is_readable($realPath)) {
            return false;
        }

        $size = @filesize($realPath);
        if ($size === false || $size <= 0) {
            return false;
        }

        $info = @getimagesize($realPath);
        if ($info === false || empty($info[0]) || empty($info[1])) {
            return false;
        }

        $mime = (string) ($info['mime'] ?? '');
        if (!in_array($mime, $allowedMimes, true)) {
            return false;
        }

        $decoded = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($realPath),
            'image/png' => @imagecreatefrompng($realPath),
            'image/webp' => @imagecreatefromwebp($realPath),
            'image/gif' => @imagecreatefromgif($realPath),
            default => false,
        };

        return $decoded instanceof GdImage;
    }

    /**
     * Normalize JPEG pixel orientation according to EXIF tag before metadata is stripped.
     */
    protected function applyExifOrientation(GdImage $resource, string $realPath): GdImage
    {
        if (!function_exists('exif_read_data')) {
            return $resource;
        }

        $exif = @exif_read_data($realPath);
        $orientation = (int) ($exif['Orientation'] ?? 1);

        if ($orientation === 1) {
            return $resource;
        }

        $rotated = match ($orientation) {
            3 => @imagerotate($resource, 180, 0),
            6 => @imagerotate($resource, -90, 0),
            8 => @imagerotate($resource, 90, 0),
            5 => @imagerotate($resource, -90, 0),
            7 => @imagerotate($resource, 90, 0),
            default => $resource,
        };

        if ($rotated instanceof GdImage) {
            $resource = $rotated;
        }

        if (in_array($orientation, [2, 5, 7], true)) {
            @imageflip($resource, IMG_FLIP_HORIZONTAL);
        } elseif ($orientation === 4) {
            @imageflip($resource, IMG_FLIP_VERTICAL);
        }

        return $resource;
    }
}

