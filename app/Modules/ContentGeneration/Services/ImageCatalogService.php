<?php

namespace App\Modules\ContentGeneration\Services;

use App\Modules\Listings\Models\ListingMedia;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class ImageCatalogService
{
    /**
     * Default source directories.
     */
    protected array $directories = [
        '/Volumes/EDGE/instagram/ellaalexandraxo',
        '/Volumes/EDGE/instagram/karionly'
    ];

    /**
     * Get all eligible, unique images that have NOT yet been used in any listing.
     *
     * @param array|null $customDirs
     * @return array<int, array{path: string, filename: string, hash: string, ext: string, width: int, height: int, mime: string, size: int}>
     */
    public function getAvailableImages(?array $customDirs = null): array
    {
        $dirs = $customDirs ?: $this->directories;
        $usedOriginalFiles = ListingMedia::pluck('original_filename')->filter()->flip()->all();

        $seenHashes = [];
        $available = [];

        foreach ($dirs as $dir) {
            if (!is_dir($dir) || !is_readable($dir)) {
                Log::warning("Image directory not accessible: {$dir}");
                continue;
            }

            $files = @scandir($dir);
            if (!$files) {
                continue;
            }

            foreach ($files as $file) {
                if ($file === '.' || $file === '..' || str_starts_with($file, '.')) {
                    continue;
                }

                $fullPath = $dir . DIRECTORY_SEPARATOR . $file;
                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

                if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                    continue;
                }

                if (isset($usedOriginalFiles[$file])) {
                    continue; // Already used in database
                }

                $hash = sha1_file($fullPath);
                if (!$hash || isset($seenHashes[$hash])) {
                    continue; // Skip perceptual / exact content duplicates
                }

                $info = @getimagesize($fullPath);
                if (!$info || $info[0] < 200 || $info[1] < 200) {
                    continue; // Skip invalid or tiny icons
                }

                $seenHashes[$hash] = true;
                $available[] = [
                    'path' => $fullPath,
                    'filename' => $file,
                    'hash' => $hash,
                    'ext' => $ext === 'jpeg' ? 'jpg' : $ext,
                    'width' => (int) $info[0],
                    'height' => (int) $info[1],
                    'mime' => $info['mime'] ?? 'image/jpeg',
                    'size' => (int) @filesize($fullPath),
                ];
            }
        }

        return $available;
    }

    /**
     * Copy validated image to public storage for a listing.
     */
    public function copyToStorage(array $imageInfo): string
    {
        $targetDir = storage_path('app/public/listings');
        if (!File::isDirectory($targetDir)) {
            File::makeDirectory($targetDir, 0755, true);
        }

        $targetFilename = $imageInfo['hash'] . '.' . $imageInfo['ext'];
        $targetPath = $targetDir . DIRECTORY_SEPARATOR . $targetFilename;

        if (!file_exists($targetPath)) {
            copy($imageInfo['path'], $targetPath);
        }

        return 'listings/' . $targetFilename;
    }
}

