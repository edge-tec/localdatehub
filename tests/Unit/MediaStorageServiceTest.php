<?php

namespace Tests\Unit;

use App\Support\Storage\MediaStorageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaStorageServiceTest extends TestCase
{
    public function test_store_sanitized_photo_outputs_clean_webp(): void
    {
        Storage::fake('local');

        $service = new MediaStorageService();
        $file = UploadedFile::fake()->image('profile_with_exif.jpg', 400, 400);

        $stored = $service->storeSanitizedPhoto($file, 'test-photos', 'local');

        $this->assertStringEndsWith('.webp', $stored['filename']);
        $this->assertStringStartsWith('test-photos/', $stored['path']);
        Storage::disk('local')->assertExists($stored['path']);
    }
}
