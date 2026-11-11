<?php

namespace App\Modules\Listings\Services;

use App\Modules\Listings\Models\Listing;
use App\Modules\Listings\Models\ListingMedia;
use App\Support\Storage\ImageProcessor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class ListingMediaService
{
    public const MAX_PHOTOS_PER_LISTING = 6;
    public const MAX_FILE_SIZE_BYTES = 5242880; // 5MB
    public const PRIMARY_MAX_WIDTH = 1600;
    public const PRIMARY_MAX_HEIGHT = 1600;
    public const THUMB_MAX_WIDTH = 600;
    public const THUMB_MAX_HEIGHT = 600;
    public const ALLOWED_MIME_TYPES = ImageProcessor::ALLOWED_PHOTO_MIMES;

    public function __construct(
        protected ?ImageProcessor $processor = null
    ) {
        $this->processor ??= new ImageProcessor();
    }

    /**
     * Validate raw uploaded image file for size, MIME type, and binary integrity.
     *
     * @throws InvalidArgumentException
     */
    public function validateUpload(UploadedFile $file): array
    {
        $realPath = $file->getRealPath();
        if (!$realPath) {
            throw new InvalidArgumentException('Uploaded photo could not be read from temporary storage.');
        }

        return $this->processor->validateFile(
            $realPath,
            self::MAX_FILE_SIZE_BYTES,
            6000,
            self::ALLOWED_MIME_TYPES
        );
    }

    /**
     * Process, normalize EXIF orientation, sanitize metadata, optimize, and atomically store photo + thumbnail.
     *
     * @throws InvalidArgumentException|RuntimeException
     */
    public function processAndStorePhoto(Listing $listing, UploadedFile $file, bool $isPrimary = false): ListingMedia
    {
        $meta = $this->validateUpload($file);

        $currentCount = $listing->media()->count();
        if ($currentCount >= self::MAX_PHOTOS_PER_LISTING) {
            throw new InvalidArgumentException('Maximum photo limit (' . self::MAX_PHOTOS_PER_LISTING . ') reached for this listing.');
        }

        // Load normalized true-color GdImage with EXIF orientation applied
        $imageResource = $this->processor->loadNormalizedImage($file->getRealPath(), $meta['mime']);
        $normalizedWidth = imagesx($imageResource);
        $normalizedHeight = imagesy($imageResource);

        $fileHash = Str::random(32);
        $storageDir = 'listings/approved';
        Storage::disk('public')->makeDirectory($storageDir);

        $fullPath = "{$storageDir}/listing_{$listing->id}_{$fileHash}.webp";
        $thumbPath = "{$storageDir}/listing_{$listing->id}_{$fileHash}_thumb.webp";

        $fullDiskPath = Storage::disk('public')->path($fullPath);
        $thumbDiskPath = Storage::disk('public')->path($thumbPath);

        $scaledImage = $this->processor->resizeKeepingAspect(
            $imageResource,
            self::PRIMARY_MAX_WIDTH,
            self::PRIMARY_MAX_HEIGHT
        );
        $fileSizeBytes = $this->processor->writeWebpAtomic($scaledImage, $fullDiskPath, 86);

        $thumbImage = $this->processor->resizeKeepingAspect(
            $imageResource,
            self::THUMB_MAX_WIDTH,
            self::THUMB_MAX_HEIGHT
        );
        $this->processor->writeWebpAtomic($thumbImage, $thumbDiskPath, 82);

        if ($isPrimary || $currentCount === 0) {
            $isPrimary = true;
            $listing->media()->update(['is_primary' => false]);
        }

        $media = ListingMedia::create([
            'listing_id' => $listing->id,
            'user_id' => $listing->user_id,
            'file_path' => $fullPath,
            'thumbnail_path' => $thumbPath,
            'original_filename' => substr($file->getClientOriginalName(), 0, 150),
            'mime_type' => 'image/webp',
            'file_size_bytes' => $fileSizeBytes,
            'width' => imagesx($scaledImage),
            'height' => imagesy($scaledImage),
            'is_primary' => $isPrimary,
            'display_order' => $currentCount + 1,
            'moderation_status' => 'approved',
        ]);

        if (!$listing->has_photos) {
            $listing->update(['has_photos' => true]);
        }

        return $media;
    }

    /**
     * Regenerate an atomic 600x600 WebP thumbnail from an existing healthy original image on disk.
     * Preserves the original source file untouched.
     *
     * @return array{thumbnail_path: string, width: int, height: int}
     */
    public function regenerateThumbnailFromOriginal(string $sourceDiskPath, string $targetDiskPath, string $targetRelativePath): array
    {
        $info = @getimagesize($sourceDiskPath);
        if ($info === false || empty($info['mime'])) {
            throw new RuntimeException("Source image is invalid: {$sourceDiskPath}");
        }

        $imageResource = $this->processor->loadNormalizedImage($sourceDiskPath, (string) $info['mime']);
        $thumbImage = $this->processor->resizeKeepingAspect(
            $imageResource,
            self::THUMB_MAX_WIDTH,
            self::THUMB_MAX_HEIGHT
        );
        $this->processor->writeWebpAtomic($thumbImage, $targetDiskPath, 82);

        return [
            'thumbnail_path' => $targetRelativePath,
            'width' => imagesx($imageResource),
            'height' => imagesy($imageResource),
        ];
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
        if ($media->file_path && Storage::disk('public')->exists($media->file_path)) {
            Storage::disk('public')->delete($media->file_path);
        }

        if ($media->thumbnail_path && Storage::disk('public')->exists($media->thumbnail_path)) {
            Storage::disk('public')->delete($media->thumbnail_path);
        }

        $listing = $media->listing;
        $wasPrimary = $media->is_primary;

        $media->delete();

        if ($wasPrimary && $listing) {
            $nextPhoto = $listing->media()->first();
            if ($nextPhoto) {
                $nextPhoto->update(['is_primary' => true]);
            } else {
                $listing->update(['has_photos' => false]);
            }
        }
    }
}
