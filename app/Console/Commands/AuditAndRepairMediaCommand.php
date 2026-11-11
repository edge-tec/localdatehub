<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Modules\Accounts\Models\UserProfile;
use App\Modules\Admin\Models\AdPlacement;
use App\Modules\Advertising\Models\AdCreative;
use App\Modules\Listings\Models\ListingMedia;
use App\Modules\Listings\Services\ListingMediaService;
use App\Support\Storage\ImageProcessor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class AuditAndRepairMediaCommand extends Command
{
    protected $signature = 'media:audit-and-repair {--dry-run : Inspect image integrity without modifying thumbnails or DB metadata}';

    protected $description = 'Non-destructively audit stored images for binary corruption and atomically regenerate missing/damaged thumbnails.';

    public function handle(ImageProcessor $processor, ListingMediaService $mediaService): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $stats = [
            'listing_media_scanned' => 0,
            'listing_originals_healthy' => 0,
            'thumbnails_regenerated' => 0,
            'dimensions_backfilled' => 0,
            'corrupted_or_missing' => [],
        ];

        $this->info('Starting non-destructive LocalDateHub media integrity audit...');

        ListingMedia::query()->chunkById(100, function ($records) use ($processor, $mediaService, $dryRun, &$stats) {
            foreach ($records as $media) {
                $stats['listing_media_scanned']++;
                $sourcePath = $this->resolveLocalDiskPath($media->file_path);

                if ($sourcePath === null) {
                    // External URL — cannot inspect on local disk
                    continue;
                }

                if (!$processor->isHealthyDiskImage($sourcePath)) {
                    $stats['corrupted_or_missing'][] = [
                        'type' => 'ListingMedia',
                        'id' => $media->id,
                        'path' => $media->file_path,
                        'reason' => is_file($sourcePath) ? 'Binary decode / GD verification failed' : 'Original file missing on disk',
                    ];
                    continue;
                }

                $stats['listing_originals_healthy']++;

                // Determine whether thumbnail needs atomic regeneration
                $thumbPath = $media->thumbnail_path;
                $thumbDiskPath = $thumbPath ? $this->resolveLocalDiskPath($thumbPath) : null;
                $isStaticSeedThumb = $thumbPath && (str_starts_with(ltrim($thumbPath, '/'), 'images/'));

                $needsThumb = empty($thumbPath)
                    || $thumbPath === $media->file_path
                    || $isStaticSeedThumb
                    || $thumbDiskPath === null
                    || !$processor->isHealthyDiskImage($thumbDiskPath);

                $needsDims = empty($media->width) || empty($media->height);

                if (!$needsThumb && !$needsDims) {
                    continue;
                }

                if ($dryRun) {
                    if ($needsThumb) {
                        $stats['thumbnails_regenerated']++;
                    }
                    if ($needsDims) {
                        $stats['dimensions_backfilled']++;
                    }
                    continue;
                }

                try {
                    if ($needsThumb) {
                        $hash = substr(sha1($media->file_path), 0, 16);
                        $targetRel = "listings/thumbs/media_{$media->id}_{$hash}_thumb.webp";
                        $targetDisk = Storage::disk('public')->path($targetRel);
                        $result = $mediaService->regenerateThumbnailFromOriginal($sourcePath, $targetDisk, $targetRel);

                        $media->thumbnail_path = $result['thumbnail_path'];
                        $media->width = $media->width ?: $result['width'];
                        $media->height = $media->height ?: $result['height'];
                        $media->save();

                        $stats['thumbnails_regenerated']++;
                    } elseif ($needsDims) {
                        $info = @getimagesize($sourcePath);
                        if ($info && !empty($info[0]) && !empty($info[1])) {
                            $media->update([
                                'width' => (int) $info[0],
                                'height' => (int) $info[1],
                            ]);
                            $stats['dimensions_backfilled']++;
                        }
                    }
                } catch (\Throwable $e) {
                    $stats['corrupted_or_missing'][] = [
                        'type' => 'ListingMedia',
                        'id' => $media->id,
                        'path' => $media->file_path,
                        'reason' => 'Thumbnail regeneration error: ' . $e->getMessage(),
                    ];
                }
            }
        });

        // Audit User avatars & Ad creative banners on local disk
        $this->auditPathCollection(User::whereNotNull('avatar')->pluck('avatar', 'id'), 'UserAvatar', $processor, $stats);
        $this->auditPathCollection(UserProfile::whereNotNull('avatar_path')->pluck('avatar_path', 'id'), 'UserProfileAvatar', $processor, $stats);
        $this->auditPathCollection(AdPlacement::whereNotNull('image_url')->pluck('image_url', 'id'), 'LegacyAdBanner', $processor, $stats);
        $this->auditPathCollection(AdCreative::whereNotNull('desktop_banner_url')->pluck('desktop_banner_url', 'id'), 'AdCreativeDesktopBanner', $processor, $stats);
        $this->auditPathCollection(AdCreative::whereNotNull('mobile_banner_url')->pluck('mobile_banner_url', 'id'), 'AdCreativeMobileBanner', $processor, $stats);

        $this->table(
            ['Metric', 'Count'],
            [
                ['ListingMedia Scanned', $stats['listing_media_scanned']],
                ['Healthy Originals Verified', $stats['listing_originals_healthy']],
                ['Thumbnails ' . ($dryRun ? 'Needing Regen' : 'Regenerated Atomically'), $stats['thumbnails_regenerated']],
                ['Dimensions Backfilled', $stats['dimensions_backfilled']],
                ['Missing / Corrupted Files', count($stats['corrupted_or_missing'])],
            ]
        );

        if (!empty($stats['corrupted_or_missing'])) {
            $this->warn('Damaged or missing files requiring attention (originals NOT deleted):');
            foreach ($stats['corrupted_or_missing'] as $issue) {
                $this->line(" - [{$issue['type']} #{$issue['id']}] {$issue['path']} => {$issue['reason']}");
            }
        }

        return self::SUCCESS;
    }

    /**
     * @param  iterable<int|string, string>  $items
     * @param  array<string, mixed>  $stats
     */
    protected function auditPathCollection(iterable $items, string $type, ImageProcessor $processor, array &$stats): void
    {
        foreach ($items as $id => $path) {
            $diskPath = $this->resolveLocalDiskPath($path);
            if ($diskPath === null) {
                continue;
            }
            if (!$processor->isHealthyDiskImage($diskPath)) {
                $stats['corrupted_or_missing'][] = [
                    'type' => $type,
                    'id' => $id,
                    'path' => $path,
                    'reason' => is_file($diskPath) ? 'Binary decode failed' : 'File missing on disk',
                ];
            }
        }
    }

    protected function resolveLocalDiskPath(?string $rawPath): ?string
    {
        $path = trim((string) $rawPath);
        if ($path === '' || str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return null;
        }

        if (str_starts_with($path, '/storage/')) {
            $relative = ltrim(substr($path, strlen('/storage/')), '/');
            return Storage::disk('public')->path($relative);
        }

        if (str_starts_with($path, 'storage/')) {
            $relative = ltrim(substr($path, strlen('storage/')), '/');
            return Storage::disk('public')->path($relative);
        }

        if (str_starts_with($path, '/images/') || str_starts_with($path, 'images/')) {
            return public_path(ltrim($path, '/'));
        }

        return Storage::disk('public')->path(ltrim($path, '/'));
    }
}
