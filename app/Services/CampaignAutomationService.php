<?php

namespace App\Services;

use App\Models\User;
use App\Modules\Categories\Models\Category;
use App\Modules\Listings\Models\Listing;
use App\Modules\Listings\Services\ListingManagementService;
use App\Modules\Listings\Services\ListingMediaService;
use App\Modules\Locations\Models\City;
use App\Modules\Locations\Models\State;
use App\Modules\Monetization\Services\PostingQuotaService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class CampaignAutomationService
{
    public const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    public function __construct(
        protected ListingManagementService $listingManagement,
        protected ListingMediaService $listingMedia,
        protected PostingQuotaService $postingQuota,
    ) {}

    /**
     * Discover profile folders and their isolated image lists.
     *
     * @return array<int, array{name: string, path: string, images: array<int, string>}>
     */
    public function discoverFolders(string $targetPath): array
    {
        $resolved = realpath($targetPath);
        if (!$resolved || !is_dir($resolved)) {
            throw new RuntimeException("Target folder does not exist or is not a directory: {$targetPath}");
        }

        $directImages = $this->listImagesInDirectory($resolved);
        $subdirectories = collect(File::directories($resolved))->sort()->values();

        // Single-profile folder mode (e.g., /Volumes/EDGE/pic file /Maisy Lowe)
        if (!empty($directImages) && $subdirectories->isEmpty()) {
            return [[
                'name' => basename($resolved),
                'path' => $resolved,
                'images' => $directImages,
            ]];
        }

        $folders = [];
        foreach ($subdirectories as $subdir) {
            $images = $this->listImagesInDirectory($subdir);
            if (!empty($images)) {
                $folders[] = [
                    'name' => basename($subdir),
                    'path' => $subdir,
                    'images' => $images,
                ];
            }
        }

        // Fallback if parent has both images and subdirectories without images
        if (empty($folders) && !empty($directImages)) {
            $folders[] = [
                'name' => basename($resolved),
                'path' => $resolved,
                'images' => $directImages,
            ];
        }

        return $folders;
    }

    /**
     * List valid image files directly inside a single folder (never crossing folders).
     *
     * @return array<int, string>
     */
    protected function listImagesInDirectory(string $directory): array
    {
        return collect(File::files($directory))
            ->filter(function ($file) {
                $ext = strtolower($file->getExtension());
                $name = $file->getFilename();

                return !str_starts_with($name, '.')
                    && in_array($ext, self::IMAGE_EXTENSIONS, true)
                    && $file->getSize() > 0;
            })
            ->map(fn ($file) => $file->getRealPath())
            ->sort()
            ->values()
            ->all();
    }

    /**
     * Resolve or create a single authorized advertiser profile for a folder without duplicating accounts.
     */
    public function resolveAdvertiserProfile(string $folderName, string $contactEmail, bool $dryRun = false): array
    {
        $cleanName = trim($folderName);
        $cleanEmail = strtolower(trim($contactEmail));

        $existingByEmail = User::whereRaw('LOWER(email) = ?', [$cleanEmail])->first();
        if ($existingByEmail) {
            if (strcasecmp($existingByEmail->name, $cleanName) !== 0) {
                throw new RuntimeException(
                    "Contact email {$cleanEmail} is already assigned to '{$existingByEmail->name}'. "
                    . "Refusing to create duplicate or mixed profile for '{$cleanName}'."
                );
            }

            return ['user' => $existingByEmail, 'created' => false];
        }

        $existingByName = User::where('name', $cleanName)->first();
        if ($existingByName) {
            return ['user' => $existingByName, 'created' => false];
        }

        if ($dryRun) {
            $virtualUser = new User([
                'name' => $cleanName,
                'email' => $cleanEmail,
                'role' => 'user',
                'account_status' => 'active',
                'is_age_verified' => true,
            ]);

            return ['user' => $virtualUser, 'created' => true];
        }

        $baseUsername = Str::slug($cleanName, '_') ?: 'advertiser';
        $username = $baseUsername;
        $suffix = 1;
        while (User::where('username', $username)->exists()) {
            $username = "{$baseUsername}_{$suffix}";
            $suffix++;
        }

        $user = User::create([
            'name' => $cleanName,
            'username' => $username,
            'email' => $cleanEmail,
            'password' => Hash::make(Str::random(32)),
            'role' => 'user',
            'account_status' => 'active',
            'is_age_verified' => true,
            'email_verified_at' => now(),
        ]);

        return ['user' => $user, 'created' => true];
    }

    /**
     * Build the verified state & city checklist from LocalDateHub's database.
     *
     * @return array{
     *     states_count: int,
     *     eligible_cities: array<int, array{city: City, state: State}>,
     *     unavailable_cities: array<int, array{city_id: int, city_name: string, state_name: string, reason: string}>
     * }
     */
    public function buildLocationChecklist(?string $stateFilter = null, ?string $cityFilter = null): array
    {
        $statesQuery = State::query()->orderBy('name');
        if ($stateFilter) {
            $needle = strtolower(trim($stateFilter));
            $statesQuery->where(function ($q) use ($needle) {
                $q->whereRaw('LOWER(code) = ?', [$needle])
                    ->orWhereRaw('LOWER(name) = ?', [$needle])
                    ->orWhereRaw('LOWER(slug) = ?', [$needle]);
            });
        }

        $states = $statesQuery->get()->keyBy('id');

        $citiesQuery = City::query()
            ->whereIn('state_id', $states->keys())
            ->orderBy('state_id')
            ->orderBy('name');

        if ($cityFilter) {
            $cityNeedle = strtolower(trim($cityFilter));
            $citiesQuery->where(function ($q) use ($cityNeedle) {
                $q->whereRaw('LOWER(name) = ?', [$cityNeedle])
                    ->orWhereRaw('LOWER(slug) = ?', [$cityNeedle]);
            });
        }

        $eligible = [];
        $unavailable = [];
        $activeStateIds = [];

        foreach ($citiesQuery->cursor() as $city) {
            /** @var State|null $state */
            $state = $states->get($city->state_id);
            if (!$state || !$state->is_active) {
                $unavailable[] = [
                    'city_id' => $city->id,
                    'city_name' => $city->name,
                    'state_name' => $state?->name ?? 'Unknown',
                    'reason' => 'State is inactive or unavailable for posting.',
                ];
                continue;
            }

            if (!$city->is_active) {
                $unavailable[] = [
                    'city_id' => $city->id,
                    'city_name' => $city->name,
                    'state_name' => $state->name,
                    'reason' => 'City is inactive or does not support posting.',
                ];
                continue;
            }

            $activeStateIds[$state->id] = true;
            $eligible[] = [
                'city' => $city,
                'state' => $state,
            ];
        }

        return [
            'states_count' => count($activeStateIds) ?: $states->where('is_active', true)->count(),
            'eligible_cities' => $eligible,
            'unavailable_cities' => $unavailable,
        ];
    }

    /**
     * Randomly select 1, 2, or 3 images from a single folder while avoiding repeated combinations.
     *
     * @param array<int, string> $folderImages
     * @param array<string, bool> $usedCombinations
     * @return array<int, string>
     */
    public function selectImagesForCity(array $folderImages, int $index, array &$usedCombinations): array
    {
        $availableCount = count($folderImages);
        if ($availableCount === 0) {
            throw new RuntimeException('No valid images found in profile folder.');
        }

        // Cycle 1, 2, 3 counts in randomized order across every 3-listing block so all 3 counts are represented
        $counts = [1, 2, 3];
        $desiredCount = min($counts[($index + random_int(0, 2)) % 3], $availableCount);

        // Ensure strict 1/2/3 variety when processing small batches (e.g., first 3 cities)
        if ($index < 3 && $availableCount >= 3) {
            $desiredCount = $index + 1;
        }

        $selected = [];
        for ($attempt = 0; $attempt < 20; $attempt++) {
            $pool = $folderImages;
            shuffle($pool);
            $candidate = array_slice($pool, 0, $desiredCount);
            $sorted = $candidate;
            sort($sorted);
            $signature = implode('|', $sorted);

            if (!isset($usedCombinations[$signature]) || $attempt === 19) {
                $usedCombinations[$signature] = true;
                $selected = $candidate;
                break;
            }
        }

        return $selected;
    }

    /**
     * Generate unique, compliant, SEO-friendly dating classified title & description for a city.
     *
     * @param array<string, bool> $usedHashes
     * @return array{title: string, description: string, content_hash: string}
     */
    public function generateSeoContent(
        string $profileName,
        string $cityName,
        string $stateName,
        string $stateCode,
        int $index,
        array &$usedHashes
    ): array {
        $titleTemplates = [
            "Meet New People in {$cityName}, {$stateCode} – Local Dating & Connections",
            "Local Dating in {$cityName} – Genuine Connections Across {$stateName}",
            "Connect With Singles in {$cityName}, {$stateCode} | {$profileName}",
            "Discover Meaningful Local Dating in {$cityName}, {$stateName}",
            "Looking to Meet Someone Special in {$cityName}, {$stateCode}?",
            "Real Conversations & Local Dating in {$cityName} ({$stateCode})",
            "Explore New Connections in {$cityName} – {$stateName} Dating Classifieds",
            "Friendly Local Dating & Social Connections in {$cityName}, {$stateCode}",
        ];

        $openers = [
            "Looking to meet new people in {$cityName}, {$stateName}?",
            "Ready to explore genuine local dating in {$cityName} ({$stateCode})?",
            "Hoping to build meaningful connections right here in {$cityName}, {$stateName}?",
            "Interested in meeting friendly, like-minded singles around {$cityName}?",
            "Searching for authentic conversation and local dating opportunities in {$cityName}, {$stateCode}?",
            "Want to connect with welcoming people in the {$cityName} community?",
        ];

        $middles = [
            "This authorized classified listing by {$profileName} is tailored for adults in {$cityName} and nearby {$stateName} neighborhoods who appreciate respectful conversation, shared local interests, and real-world compatibility.",
            "Whether you enjoy coffee meetups, weekend walks, local dining, or relaxed evenings out in {$cityName}, connecting locally makes getting to know someone natural and effortless.",
            "Published by authorized advertiser {$profileName}, this {$cityName}, {$stateCode} dating notice welcomes thoughtful introductions from local adults seeking friendship, companionship, or lasting romance.",
            "From casual first chats to memorable dates around {$cityName}, our goal is to help local singles in {$stateName} connect openly, safely, and at their own pace.",
            "Instead of endless swiping, discover a straightforward way to meet new people in {$cityName}, {$stateCode} who value honesty, kindness, and genuine chemistry.",
            "If you call {$cityName} or the surrounding {$stateName} area home and enjoy meaningful conversation, take a moment to reach out and introduce yourself.",
        ];

        $ctas = [
            "Send a message through LocalDateHub today to start a friendly conversation in {$cityName}.",
            "Reply via our secure LocalDateHub message relay to introduce yourself and connect locally in {$cityName}, {$stateCode}.",
            "Reach out today with a quick introduction and start connecting with local singles in {$cityName}.",
            "Create your LocalDateHub profile or send a direct inquiry now to connect in the {$cityName} area.",
        ];

        for ($variant = 0; $variant < 24; $variant++) {
            $tIdx = ($index + $variant) % count($titleTemplates);
            $oIdx = ($index + $variant * 3) % count($openers);
            $mIdx = ($index + $variant * 5) % count($middles);
            $cIdx = ($index + $variant * 7) % count($ctas);

            $title = $titleTemplates[$tIdx];
            if ($variant >= count($titleTemplates)) {
                $title .= ' #' . ($variant + 1);
            }

            $description = "{$openers[$oIdx]} {$middles[$mIdx]} {$ctas[$cIdx]}";

            $normalized = strtolower(trim(preg_replace('/\s+/', ' ', $title . ' ' . $description)));
            $hash = hash('sha256', $normalized);

            if (isset($usedHashes[$hash])) {
                continue;
            }

            // Enforce platform compliance and duplicate detection
            $this->listingManagement->validateContentCompliance($title, $description);
            if ($this->listingManagement->detectDuplicate($title, $description)) {
                continue;
            }

            $usedHashes[$hash] = true;

            return [
                'title' => $title,
                'description' => $description,
                'content_hash' => $hash,
            ];
        }

        throw new RuntimeException("Unable to generate non-duplicate compliant SEO content for {$cityName}, {$stateCode}.");
    }

    /**
     * Execute the full folder-based campaign automation and generate a detailed report.
     *
     * @param array{
     *     category?: string|int|null,
     *     age?: int,
     *     gender?: string,
     *     intent?: string,
     *     state?: string|null,
     *     city?: string|null,
     *     limit?: int,
     *     dry_run?: bool,
     *     report_dir?: string
     * } $options
     */
    public function runCampaign(string $targetPath, string $contactEmail, array $options = []): array
    {
        $dryRun = (bool) ($options['dry_run'] ?? false);
        $limit = max(0, (int) ($options['limit'] ?? 0));
        $posterAge = max(18, (int) ($options['age'] ?? 25));
        $posterGender = (string) ($options['gender'] ?? 'female');
        $intent = (string) ($options['intent'] ?? 'dating');

        $category = $this->resolveCategory($options['category'] ?? null);
        $folders = $this->discoverFolders($targetPath);
        if (empty($folders)) {
            throw new RuntimeException("No folders with valid images found in: {$targetPath}");
        }

        $checklist = $this->buildLocationChecklist($options['state'] ?? null, $options['city'] ?? null);
        $eligibleCities = $checklist['eligible_cities'];
        if ($limit > 0) {
            $eligibleCities = array_slice($eligibleCities, 0, $limit);
        }

        $report = [
            'started_at' => now()->toIso8601String(),
            'dry_run' => $dryRun,
            'target_path' => $targetPath,
            'contact_email' => $contactEmail,
            'category' => ['id' => $category->id, 'name' => $category->name, 'slug' => $category->slug],
            'total_folders_discovered' => count($folders),
            'total_profiles_created' => 0,
            'total_profiles_reused' => 0,
            'total_states_processed' => $checklist['states_count'],
            'total_cities_eligible' => count($checklist['eligible_cities']),
            'total_cities_processed' => 0,
            'total_ads_published' => 0,
            'total_ads_pending_review' => 0,
            'total_images_uploaded' => 0,
            'image_count_breakdown' => [1 => 0, 2 => 0, 3 => 0],
            'duplicate_content_detected' => 0,
            'failed_advertisements' => [],
            'unavailable_cities' => $checklist['unavailable_cities'],
            'published_listings' => [],
            'profiles' => [],
        ];

        $usedContentHashes = [];

        foreach ($folders as $folder) {
            $profileResult = $this->resolveAdvertiserProfile($folder['name'], $contactEmail, $dryRun);
            /** @var User $user */
            $user = $profileResult['user'];
            if ($profileResult['created']) {
                $report['total_profiles_created']++;
            } else {
                $report['total_profiles_reused']++;
            }

            $report['profiles'][] = [
                'name' => $folder['name'],
                'email' => $contactEmail,
                'user_id' => $user->id,
                'created' => $profileResult['created'],
                'image_pool_count' => count($folder['images']),
            ];

            $usedImageCombos = [];
            $quotaBlockedReason = null;
            $existingCityIds = !$dryRun && $user->id
                ? Listing::where('user_id', $user->id)
                    ->whereIn('status', [Listing::STATUS_ACTIVE, Listing::STATUS_PENDING_REVIEW])
                    ->pluck('city_id')
                    ->flip()
                    ->all()
                : [];

            foreach ($eligibleCities as $cityIndex => $entry) {
                /** @var City $city */
                $city = $entry['city'];
                /** @var State $state */
                $state = $entry['state'];

                if (isset($existingCityIds[$city->id])) {
                    continue;
                }

                $report['total_cities_processed']++;

                if ($quotaBlockedReason !== null) {
                    $report['failed_advertisements'][] = [
                        'profile' => $folder['name'],
                        'city' => $city->name,
                        'state' => $state->code,
                        'reason' => $quotaBlockedReason,
                    ];
                    continue;
                }

                if (!$dryRun) {
                    $quotaSummary = $this->postingQuota->getUserQuotaSummary($user);
                    if (!$quotaSummary['can_post']) {
                        $quotaBlockedReason = $quotaSummary['blocked_reason'] ?: 'Posting quota reached.';
                        $report['failed_advertisements'][] = [
                            'profile' => $folder['name'],
                            'city' => $city->name,
                            'state' => $state->code,
                            'reason' => $quotaBlockedReason,
                        ];
                        continue;
                    }
                }

                try {
                    $seo = $this->generateSeoContent(
                        $folder['name'],
                        $city->name,
                        $state->name,
                        $state->code,
                        $cityIndex,
                        $usedContentHashes
                    );
                } catch (Throwable $e) {
                    if (str_contains(strtolower($e->getMessage()), 'duplicate')) {
                        $report['duplicate_content_detected']++;
                    }
                    $report['failed_advertisements'][] = [
                        'profile' => $folder['name'],
                        'city' => $city->name,
                        'state' => $state->code,
                        'reason' => $e->getMessage(),
                    ];
                    continue;
                }

                $selectedImages = $this->selectImagesForCity($folder['images'], $cityIndex, $usedImageCombos);
                $imageCount = count($selectedImages);

                if ($dryRun) {
                    $report['image_count_breakdown'][$imageCount] = ($report['image_count_breakdown'][$imageCount] ?? 0) + 1;
                    $report['total_images_uploaded'] += $imageCount;
                    $report['published_listings'][] = [
                        'profile' => $folder['name'],
                        'listing_id' => null,
                        'reference_code' => 'DRY-RUN',
                        'status' => 'dry_run_valid',
                        'title' => $seo['title'],
                        'city' => $city->name,
                        'state' => $state->code,
                        'image_count' => $imageCount,
                        'images' => array_map('basename', $selectedImages),
                        'url' => null,
                    ];
                    continue;
                }

                $draft = null;
                try {
                    $draft = $this->listingManagement->saveDraft($user, [
                        'title' => $seo['title'],
                        'description' => $seo['description'],
                        'poster_age' => $posterAge,
                        'poster_gender' => $posterGender,
                        'intent' => $intent,
                        'category_id' => $category->id,
                        'state_id' => $state->id,
                        'city_id' => $city->id,
                        'preferred_email' => $contactEmail,
                        'allow_platform_messages' => true,
                        'allow_email_relay' => true,
                        'preferred_contact_method' => 'platform_message',
                        'attributes' => [
                            'intent' => $intent,
                        ],
                    ]);

                    foreach ($selectedImages as $imgIdx => $imagePath) {
                        $mime = mime_content_type($imagePath) ?: 'image/jpeg';
                        $uploaded = new UploadedFile($imagePath, basename($imagePath), $mime, null, true);
                        $this->listingMedia->processAndStorePhoto($draft, $uploaded, $imgIdx === 0);
                    }

                    $published = $this->listingManagement->submitForReview($draft, $user, [
                        'attributes' => [
                            'intent' => $intent,
                        ],
                    ]);
                    $url = route('listings.show', $published->slug);

                    $report['image_count_breakdown'][$imageCount] = ($report['image_count_breakdown'][$imageCount] ?? 0) + 1;
                    $report['total_images_uploaded'] += $imageCount;

                    if ($published->status === Listing::STATUS_ACTIVE) {
                        $report['total_ads_published']++;
                    } elseif ($published->status === Listing::STATUS_PENDING_REVIEW) {
                        $report['total_ads_pending_review']++;
                    }

                    $report['published_listings'][] = [
                        'profile' => $folder['name'],
                        'listing_id' => $published->id,
                        'reference_code' => $published->reference_code,
                        'status' => $published->status,
                        'title' => $published->title,
                        'city' => $city->name,
                        'state' => $state->code,
                        'image_count' => $imageCount,
                        'images' => array_map('basename', $selectedImages),
                        'url' => $url,
                    ];
                } catch (Throwable $e) {
                    if ($draft && $draft->exists) {
                        foreach ($draft->media as $media) {
                            $this->listingMedia->deleteMedia($media);
                        }
                        $draft->forceDelete();
                    }

                    $msg = $e->getMessage();
                    if (str_contains(strtolower($msg), 'duplicate')) {
                        $report['duplicate_content_detected']++;
                    }
                    if (str_contains(strtolower($msg), 'limit') || str_contains(strtolower($msg), 'quota') || str_contains(strtolower($msg), 'credit')) {
                        $quotaBlockedReason = $msg;
                    }

                    $report['failed_advertisements'][] = [
                        'profile' => $folder['name'],
                        'city' => $city->name,
                        'state' => $state->code,
                        'reason' => $msg,
                    ];
                }
            }
        }

        $report['completed_at'] = now()->toIso8601String();
        $reportDir = $options['report_dir'] ?? storage_path('app/campaign-reports');
        $report['report_files'] = $this->writeReports($report, $reportDir);

        return $report;
    }

    protected function resolveCategory(string|int|null $categoryOption): Category
    {
        if (is_numeric($categoryOption)) {
            $cat = Category::where('id', (int) $categoryOption)->where('is_active', true)->first();
            if ($cat) {
                return $cat;
            }
        } elseif (is_string($categoryOption) && $categoryOption !== '') {
            $cat = Category::where('slug', $categoryOption)->where('is_active', true)->first();
            if ($cat) {
                return $cat;
            }
        }

        $default = Category::where('slug', 'women-seeking-men')->where('is_active', true)->first()
            ?? Category::where('is_active', true)->whereNotNull('parent_id')->orderBy('id')->first()
            ?? Category::where('is_active', true)->orderBy('id')->first();

        if (!$default) {
            throw new RuntimeException('No active listing category found in database.');
        }

        return $default;
    }

    /**
     * Persist JSON and Markdown reports to disk.
     *
     * @return array{json: string, markdown: string}
     */
    protected function writeReports(array $report, string $reportDir): array
    {
        File::ensureDirectoryExists($reportDir);
        $stamp = now()->format('Ymd_His');
        $jsonPath = "{$reportDir}/campaign_report_{$stamp}.json";
        $mdPath = "{$reportDir}/campaign_report_{$stamp}.md";

        File::put($jsonPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $lines = [
            '# Classified Dating Campaign Report',
            '',
            '- **Started:** ' . $report['started_at'],
            '- **Completed:** ' . $report['completed_at'],
            '- **Mode:** ' . ($report['dry_run'] ? 'Dry Run (Preview)' : 'Live'),
            '- **Target Path:** `' . $report['target_path'] . '`',
            '- **Contact Email:** ' . $report['contact_email'],
            '- **Category:** ' . $report['category']['name'] . ' (`' . $report['category']['slug'] . '`)',
            '',
            '## Summary Metrics',
            '',
            '| Metric | Count |',
            '| --- | ---: |',
            '| Total folders discovered | ' . $report['total_folders_discovered'] . ' |',
            '| Total authorized profiles created | ' . $report['total_profiles_created'] . ' |',
            '| Total existing profiles reused | ' . $report['total_profiles_reused'] . ' |',
            '| Total states processed | ' . $report['total_states_processed'] . ' |',
            '| Total eligible cities | ' . $report['total_cities_eligible'] . ' |',
            '| Total cities processed | ' . $report['total_cities_processed'] . ' |',
            '| Total advertisements published (active) | ' . $report['total_ads_published'] . ' |',
            '| Total advertisements pending review | ' . $report['total_ads_pending_review'] . ' |',
            '| Total images uploaded | ' . $report['total_images_uploaded'] . ' |',
            '| 1-image advertisements | ' . ($report['image_count_breakdown'][1] ?? 0) . ' |',
            '| 2-image advertisements | ' . ($report['image_count_breakdown'][2] ?? 0) . ' |',
            '| 3-image advertisements | ' . ($report['image_count_breakdown'][3] ?? 0) . ' |',
            '| Failed / quota-blocked advertisements | ' . count($report['failed_advertisements']) . ' |',
            '| Duplicate content detected | ' . $report['duplicate_content_detected'] . ' |',
            '| Cities unavailable for posting | ' . count($report['unavailable_cities']) . ' |',
            '',
        ];

        if (!empty($report['published_listings'])) {
            $lines[] = '## Submitted Listings';
            $lines[] = '';
            $lines[] = '| Profile | City, State | Status | Images | URL |';
            $lines[] = '| --- | --- | --- | ---: | --- |';
            foreach ($report['published_listings'] as $item) {
                $url = $item['url'] ? "[{$item['url']}]({$item['url']})" : 'N/A (dry-run)';
                $lines[] = "| {$item['profile']} | {$item['city']}, {$item['state']} | {$item['status']} | {$item['image_count']} | {$url} |";
            }
            $lines[] = '';
        }

        if (!empty($report['failed_advertisements'])) {
            $lines[] = '## Failed / Quota-Blocked Advertisements (First 50)';
            $lines[] = '';
            foreach (array_slice($report['failed_advertisements'], 0, 50) as $fail) {
                $lines[] = "- **{$fail['profile']}** ({$fail['city']}, {$fail['state']}): {$fail['reason']}";
            }
            $lines[] = '';
        }

        File::put($mdPath, implode("\n", $lines));

        return ['json' => $jsonPath, 'markdown' => $mdPath];
    }
}
