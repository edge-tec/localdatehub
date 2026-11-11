<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Categories\Models\Category;
use App\Modules\Listings\Models\Listing;
use App\Modules\Listings\Models\ListingMedia;
use App\Modules\Listings\Services\ListingManagementService;
use App\Modules\Listings\Services\ListingMediaService;
use App\Modules\Locations\Models\City;
use App\Support\Storage\ImageProcessor;
use App\Support\Storage\MediaStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Tests\TestCase;

class ImageIntegrityAndPipelineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake('public');
    }

    protected function createSampleListing(User $user): Listing
    {
        $category = Category::where('slug', 'women-seeking-men')->first() ?? Category::first();
        $city = City::first();

        return app(ListingManagementService::class)->saveDraft($user, [
            'category_id' => $category->id,
            'state_id' => $city->state_id,
            'city_id' => $city->id,
            'title' => 'Image Integrity Verified Classified Listing',
            'description' => 'Comprehensive image pipeline verification listing description text.',
            'poster_age' => 29,
            'poster_gender' => 'Female',
            'intent' => 'dating',
        ]);
    }

    public function test_processes_jpeg_palette_png_webp_and_large_portrait_landscape_preserving_aspect_ratio(): void
    {
        $user = User::factory()->create(['is_age_verified' => true, 'is_identity_verified' => true]);
        $listing = $this->createSampleListing($user);
        $mediaService = app(ListingMediaService::class);
        $processor = app(ImageProcessor::class);

        // 1. Large Portrait JPEG (1800x2400 -> primary 1200x1600, thumb 450x600)
        $portraitPath = sys_get_temp_dir() . '/test_portrait_' . uniqid() . '.jpg';
        $imPortrait = imagecreatetruecolor(1800, 2400);
        imagejpeg($imPortrait, $portraitPath, 90);
        $portraitFile = new UploadedFile($portraitPath, 'portrait.jpg', 'image/jpeg', null, true);

        $mediaPortrait = $mediaService->processAndStorePhoto($listing, $portraitFile, true);
        $this->assertEquals(1200, $mediaPortrait->width);
        $this->assertEquals(1600, $mediaPortrait->height);
        $this->assertTrue($processor->isHealthyDiskImage(Storage::disk('public')->path($mediaPortrait->file_path)));
        $this->assertTrue($processor->isHealthyDiskImage(Storage::disk('public')->path($mediaPortrait->thumbnail_path)));
        $thumbInfo = getimagesize(Storage::disk('public')->path($mediaPortrait->thumbnail_path));
        $this->assertEquals([450, 600], [$thumbInfo[0], $thumbInfo[1]]);
        @unlink($portraitPath);

        // 2. 8-bit Indexed Palette PNG with Transparency
        $pngPath = sys_get_temp_dir() . '/test_palette_' . uniqid() . '.png';
        $imPalette = imagecreate(500, 250);
        $bg = imagecolorallocate($imPalette, 255, 0, 128);
        imagecolortransparent($imPalette, $bg);
        imagepng($imPalette, $pngPath);
        $pngFile = new UploadedFile($pngPath, 'palette.png', 'image/png', null, true);

        $mediaPng = $mediaService->processAndStorePhoto($listing, $pngFile);
        $this->assertEquals('image/webp', $mediaPng->mime_type);
        $this->assertEquals(500, $mediaPng->width);
        $this->assertEquals(250, $mediaPng->height);
        $this->assertTrue($processor->isHealthyDiskImage(Storage::disk('public')->path($mediaPng->file_path)));
        $this->assertTrue($processor->isHealthyDiskImage(Storage::disk('public')->path($mediaPng->thumbnail_path)));
        @unlink($pngPath);

        // 3. Large Landscape WebP (2400x1200 -> primary 1600x800, thumb 600x300)
        $webpPath = sys_get_temp_dir() . '/test_landscape_' . uniqid() . '.webp';
        $imLandscape = imagecreatetruecolor(2400, 1200);
        imagewebp($imLandscape, $webpPath, 90);
        $webpFile = new UploadedFile($webpPath, 'landscape.webp', 'image/webp', null, true);

        $mediaWebp = $mediaService->processAndStorePhoto($listing, $webpFile);
        $this->assertEquals(1600, $mediaWebp->width);
        $this->assertEquals(800, $mediaWebp->height);
        $thumbInfoLandscape = getimagesize(Storage::disk('public')->path($mediaWebp->thumbnail_path));
        $this->assertEquals([600, 300], [$thumbInfoLandscape[0], $thumbInfoLandscape[1]]);
        @unlink($webpPath);

        // Ensure zero stale atomic temp files left behind
        $allFiles = Storage::disk('public')->allFiles('listings/approved');
        $tmpFiles = array_filter($allFiles, fn ($p) => str_contains($p, '.tmp.'));
        $this->assertEmpty($tmpFiles);
    }

    public function test_exif_orientation_is_normalized_and_exif_metadata_is_stripped(): void
    {
        if (!function_exists('exif_read_data')) {
            $this->markTestSkipped('EXIF extension not enabled.');
        }

        // Build an 800x400 JPEG injected with an APP1 EXIF Orientation=6 (90 deg CW rotate => 400x800)
        $jpegPath = sys_get_temp_dir() . '/test_exif6_' . uniqid() . '.jpg';
        $im = imagecreatetruecolor(800, 400);
        ob_start();
        imagejpeg($im, null, 90);
        $rawJpeg = ob_get_clean();

        // Construct minimal valid TIFF IFD0 little-endian block with 1 tag: Orientation (0x0112) = 6
        $tiff = "II\x2A\x00\x08\x00\x00\x00" // Header + offset 8
            . "\x01\x00"                    // 1 IFD entry
            . "\x12\x01\x03\x00\x01\x00\x00\x00\x06\x00\x00\x00" // Tag 0x0112 SHORT count 1 value 6
            . "\x00\x00\x00\x00";           // Next IFD = 0
        $app1Data = "Exif\x00\x00" . $tiff;
        $app1Segment = "\xFF\xE1" . pack('n', strlen($app1Data) + 2) . $app1Data;
        $jpegWithExif = "\xFF\xD8" . $app1Segment . substr($rawJpeg, 2);
        file_put_contents($jpegPath, $jpegWithExif);

        $exifBefore = @exif_read_data($jpegPath);
        $this->assertEquals(6, (int) ($exifBefore['Orientation'] ?? 0));

        $user = User::factory()->create(['is_age_verified' => true]);
        $listing = $this->createSampleListing($user);
        $file = new UploadedFile($jpegPath, 'camera_rotated.jpg', 'image/jpeg', null, true);

        $media = app(ListingMediaService::class)->processAndStorePhoto($listing, $file, true);

        // After Orientation=6 normalization, 800x400 becomes portrait 400x800
        $this->assertEquals(400, $media->width);
        $this->assertEquals(800, $media->height);
        $storedPath = Storage::disk('public')->path($media->file_path);
        $this->assertSame('image/webp', getimagesize($storedPath)['mime']);
        @unlink($jpegPath);
    }

    public function test_malicious_polyglot_or_corrupted_file_claiming_jpeg_extension_is_rejected(): void
    {
        $user = User::factory()->create(['is_age_verified' => true]);
        $listing = $this->createSampleListing($user);

        $badPath = sys_get_temp_dir() . '/malicious_' . uniqid() . '.jpg';
        file_put_contents($badPath, '<?php echo "malicious"; ?> not a real jpeg binary');
        $fakeJpeg = new UploadedFile($badPath, 'evil.jpg', 'image/jpeg', null, true);

        try {
            $this->expectException(InvalidArgumentException::class);
            app(ListingMediaService::class)->processAndStorePhoto($listing, $fakeJpeg, true);
        } finally {
            @unlink($badPath);
        }
    }

    public function test_media_storage_service_never_falls_back_to_non_webp_bytes(): void
    {
        $service = app(MediaStorageService::class);
        $file = UploadedFile::fake()->image('avatar.png', 500, 300);

        $stored = $service->storeSanitizedPhoto($file, 'avatars', 'public', 800, 800);
        $diskPath = Storage::disk('public')->path($stored['path']);

        $this->assertSame('image/webp', getimagesize($diskPath)['mime']);
        $this->assertEquals(500, $stored['width']);
        $this->assertEquals(300, $stored['height']);
    }

    public function test_audit_and_repair_command_regenerates_corrupted_thumbnail_without_touching_original(): void
    {
        $user = User::factory()->create(['is_age_verified' => true]);
        $listing = $this->createSampleListing($user);
        $mediaService = app(ListingMediaService::class);
        $processor = app(ImageProcessor::class);

        $file = UploadedFile::fake()->image('gallery_photo.jpg', 900, 1200);
        $media = $mediaService->processAndStorePhoto($listing, $file, true);

        $originalDiskPath = Storage::disk('public')->path($media->file_path);
        $thumbDiskPath = Storage::disk('public')->path($media->thumbnail_path);
        $originalSha1 = sha1_file($originalDiskPath);

        // Simulate corrupted/truncated thumbnail on disk
        file_put_contents($thumbDiskPath, 'corrupted-partial-bytes');
        $this->assertFalse($processor->isHealthyDiskImage($thumbDiskPath));

        Artisan::call('media:audit-and-repair');

        $media->refresh();
        $repairedThumbDiskPath = Storage::disk('public')->path($media->thumbnail_path);

        // Original byte integrity 100% preserved, repaired thumbnail valid
        $this->assertSame($originalSha1, sha1_file($originalDiskPath));
        $this->assertTrue($processor->isHealthyDiskImage($repairedThumbDiskPath));
    }

    public function test_listing_gallery_and_homepage_render_single_clean_images_without_gpu_blur_backdrops(): void
    {
        $user = User::factory()->create(['is_age_verified' => true, 'is_identity_verified' => true]);
        $listing = $this->createSampleListing($user);
        app(ListingMediaService::class)->processAndStorePhoto(
            $listing,
            UploadedFile::fake()->image('member_gallery.jpg', 800, 1000),
            true
        );
        $listing->update(['status' => Listing::STATUS_ACTIVE, 'published_at' => now()]);

        $detailResponse = $this->get(route('listings.show', $listing->slug));
        $detailResponse->assertStatus(200);
        $detailResponse->assertSee('Verified Member Photo Gallery');
        $detailResponse->assertDontSee('filter blur-lg scale-110');
        $detailResponse->assertSee('decoding="async"', false);
        $detailResponse->assertSee('object-contain', false);

        $homeResponse = $this->get(route('home'));
        $homeResponse->assertStatus(200);
        $homeResponse->assertDontSee('filter blur-lg scale-110');
    }
}

