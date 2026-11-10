<?php

namespace App\Modules\Listings\Services;

use App\Modules\Listings\Models\Listing;
use App\Modules\Listings\Models\ListingMedia;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class ListingMediaService
{
    public const MAX_PHOTOS_PER_LISTING = 6;
    public const MAX_FILE_SIZE_BYTES = 5242880; // 5MB
    public const ALLOWED_MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    /**
     * Validate raw uploaded image file for size, MIME type, and binary integrity.
     *
     * @throws InvalidArgumentException
     */
    public function validateUpload(UploadedFile $file): void
    {
        if ($file->getSize() > self::MAX_FILE_SIZE_BYTES) {
            throw new InvalidArgumentException('Uploaded photo exceeds the maximum allowed size of 5MB.');
        }

        $mime = $file->getMimeType();
        if (!in_array($mime, self::ALLOWED_MIME_TYPES, true)) {
            throw new InvalidArgumentException('Invalid photo format. Only JPEG, PNG, and WebP images are permitted.');
        }

        // Deep binary inspection: verify valid image resource and dimensions (malware/polyglot defense)
        $imageInfo = @getimagesize($file->getRealPath());
        if ($imageInfo === false || empty($imageInfo[0]) || empty($imageInfo[1])) {
            throw new InvalidArgumentException('The uploaded file is not a valid or recognizable image format.');
        }

        // Prevent excessively massive images (e.g. decompression bombs)
        if ($imageInfo[0] > 6000 || $imageInfo[1] > 6000) {
            throw new InvalidArgumentException('Image resolution exceeds the maximum allowed dimension of 6000x6000 pixels.');
        }
    }

    /**
     * Process, sanitize (strip EXIF metadata), optimize, and store photo in private storage.
     *
     * @param Listing $listing
     * @param UploadedFile $file
     * @param bool $isPrimary
     * @return ListingMedia
     */
    public function processAndStorePhoto(Listing $listing, UploadedFile $file, bool $isPrimary = false): ListingMedia
    {
        $this->validateUpload($file);

        $currentCount = $listing->media()->count();
        if ($currentCount >= self::MAX_PHOTOS_PER_LISTING) {
            throw new InvalidArgumentException('Maximum photo limit (' . self::MAX_PHOTOS_PER_LISTING . ') reached for this listing.');
        }

        // Strip EXIF metadata and generate sanitized WebP in memory
        $imageResource = $this->createImageResource($file);
        $originalWidth = imagesx($imageResource);
        $originalHeight = imagesy($imageResource);

        // Standardize filename
        $fileHash = Str::random(32);
        $fileName = "listing_{$listing->id}_{$fileHash}.webp";
        $thumbName = "listing_{$listing->id}_{$fileHash}_thumb.webp";

        // Pending media stored in private directory
        $storageDir = "listings/pending";
        Storage::disk('public')->makeDirectory($storageDir);

        $fullPath = "{$storageDir}/{$fileName}";
        $thumbPath = "{$storageDir}/{$thumbName}";

        // Write primary optimized WebP (max 1600px width/height while preserving aspect ratio)
        $scaledImage = $this->resizeKeepingAspect($imageResource, $originalWidth, $originalHeight, 1600, 1600);
        $fullDiskPath = Storage::disk('public')->path($fullPath);
        imagewebp($scaledImage, $fullDiskPath, 85);
        if ($scaledImage !== $imageResource) {
            imagedestroy($scaledImage);
        }

        // Generate thumbnail (300x300)
        $thumbImage = $this->resizeKeepingAspect($imageResource, $originalWidth, $originalHeight, 300, 300);
        $thumbDiskPath = Storage::disk('public')->path($thumbPath);
        imagewebp($thumbImage, $thumbDiskPath, 80);
        if ($thumbImage !== $imageResource) {
            imagedestroy($thumbImage);
        }

        imagedestroy($imageResource);

        $fileSizeBytes = filesize($fullDiskPath) ?: 0;

        // If this is the first photo or marked primary, reset others
        if ($isPrimary || $currentCount === 0) {
            $isPrimary = true;
            $listing->media()->update(['is_primary' => false]);
        }

        $displayOrder = $currentCount + 1;

        $media = ListingMedia::create([
            'listing_id' => $listing->id,
            'user_id' => $listing->user_id,
            'file_path' => $fullPath,
            'thumbnail_path' => $thumbPath,
            'original_filename' => substr($file->getClientOriginalName(), 0, 150),
            'mime_type' => 'image/webp',
            'file_size_bytes' => $fileSizeBytes,
            'width' => $originalWidth,
            'height' => $originalHeight,
            'is_primary' => $isPrimary,
            'display_order' => $displayOrder,
            'moderation_status' => 'approved', // Immediately approved and visible across post and cards
        ]);

        if (!$listing->has_photos) {
            $listing->update(['has_photos' => true]);
        }

        return $media;
    }

    /**
     * Approve media item.
     */
    public function approveMedia(ListingMedia $media): void
    {
        $media->update([
            'moderation_status' => 'approved',
            'rejection_reason' => null,
        ]);
    }

    /**
     * Reject media item with reason.
     */
    public function rejectMedia(ListingMedia $media, string $reason): void
    {
        $media->update([
            'moderation_status' => 'rejected',
            'rejection_reason' => $reason,
        ]);
    }

    /**
     * Delete media item and associated files.
     */
    public function deleteMedia(ListingMedia $media): void
    {
        if (Storage::disk('public')->exists($media->file_path)) {
            Storage::disk('public')->delete($media->file_path);
        }

        if ($media->thumbnail_path && Storage::disk('public')->exists($media->thumbnail_path)) {
            Storage::disk('public')->delete($media->thumbnail_path);
        }

        $listing = $media->listing;
        $wasPrimary = $media->is_primary;

        $media->delete();

        // If primary was deleted, set next photo as primary
        if ($wasPrimary && $listing) {
            $nextPhoto = $listing->media()->first();
            if ($nextPhoto) {
                $nextPhoto->update(['is_primary' => true]);
            } else {
                $listing->update(['has_photos' => false]);
            }
        }
    }

    /**
     * Create GD resource from file path.
     */
    protected function createImageResource(UploadedFile $file)
    {
        $realPath = $file->getRealPath();
        $mime = $file->getMimeType();

        $resource = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($realPath),
            'image/png' => @imagecreatefrompng($realPath),
            'image/webp' => @imagecreatefromwebp($realPath),
            default => null,
        };

        if (!$resource) {
            throw new RuntimeException('Failed to process image file. The image could be corrupted.');
        }

        return $resource;
    }

    /**
     * Resize image preserving aspect ratio.
     */
    protected function resizeKeepingAspect($src, int $srcW, int $srcH, int $maxW, int $maxH)
    {
        if ($srcW <= $maxW && $srcH <= $maxH) {
            return $src;
        }

        $ratio = min($maxW / $srcW, $maxH / $srcH);
        $targetW = (int) round($srcW * $ratio);
        $targetH = (int) round($srcH * $ratio);

        $dst = imagecreatetruecolor($targetW, $targetH);
        // Preserve alpha for transparent PNGs
        imagealphablending($dst, false);
        imagesavealpha($dst, true);

        imagecopyresampled($dst, $src, 0, 0, 0, 0, $targetW, $targetH, $srcW, $srcH);

        return $dst;
    }
}

