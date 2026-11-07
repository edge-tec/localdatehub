<?php

namespace App\Modules\Listings\Services;

use App\Models\User;
use App\Modules\Categories\Models\Category;
use App\Modules\Categories\Services\CategoryService;
use App\Modules\Listings\Models\Listing;
use App\Modules\Listings\Models\ListingAttribute;
use App\Modules\Listings\Models\ListingContact;
use App\Modules\Listings\Models\ListingLocation;
use App\Modules\Locations\DTOs\CoordinateDTO;
use App\Modules\Locations\Models\City;
use App\Modules\Locations\Models\State;
use App\Modules\Locations\Models\ZipCode;
use App\Modules\Locations\Services\LocationService;
use App\Support\Exceptions\AgeVerificationException;
use App\Support\Exceptions\ProhibitedContentException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class ListingManagementService
{
    /**
     * Prohibited keywords and solicitation markers under federal safety standards & FOSTA-SESTA.
     */
    protected array $prohibitedPatterns = [
        '/\b(escort|prostitut|ppm|pay\s*per\s*meet|hourly\s*rate|donation\s*for\s*time|full\s*service|happy\s*ending)\b/i',
        '/\b(incall|outcall|roses|compensation\s*required|sugar\s*baby|sugar\s*daddy)\b/i',
        '/\b(underage|teen|minor|barely\s*legal)\b/i',
        '/\b(cashing|venmo\s*first|cashapp\s*deposit|crypto\s*gift)\b/i',
    ];

    public function __construct(
        protected LocationService $locationService,
        protected CategoryService $categoryService,
        protected ListingMediaService $mediaService
    ) {}

    /**
     * Check text for prohibited or non-compliant commercial solicitation terms.
     *
     * @throws ProhibitedContentException
     */
    public function validateContentCompliance(string $title, string $description): void
    {
        $text = "{$title} {$description}";
        foreach ($this->prohibitedPatterns as $pattern) {
            if (preg_match($pattern, $text)) {
                throw new ProhibitedContentException(
                    'Your listing contains prohibited commercial terms or non-compliant language under LocalAdZone community safety standards.'
                );
            }
        }
    }

    /**
     * Check if a near-identical listing was created recently (within last 7 days).
     */
    public function detectDuplicate(string $title, string $description, ?int $ignoreListingId = null): ?Listing
    {
        $normalized = strtolower(preg_replace('/\s+/', ' ', trim($title . ' ' . $description)));
        $hash = hash('sha256', $normalized);

        return Listing::where('content_hash', $hash)
            ->whereIn('status', [Listing::STATUS_ACTIVE, Listing::STATUS_PENDING_REVIEW])
            ->where('created_at', '>=', now()->subDays(7))
            ->when($ignoreListingId, fn ($q) => $q->where('id', '!=', $ignoreListingId))
            ->first();
    }

    /**
     * Create or update draft listing.
     *
     * @param User $user
     * @param array<string, mixed> $data
     * @param int|null $existingDraftId
     * @return Listing
     */
    public function saveDraft(User $user, array $data, ?int $existingDraftId = null): Listing
    {
        return DB::transaction(function () use ($user, $data, $existingDraftId) {
            $listing = null;
            if ($existingDraftId) {
                $listing = Listing::where('id', $existingDraftId)
                    ->where('user_id', $user->id)
                    ->first();
            }

            $title = !empty($data['title']) ? trim($data['title']) : 'Untitled Draft';
            $description = !empty($data['description']) ? trim($data['description']) : '';
            $categoryId = !empty($data['category_id']) ? (int) $data['category_id'] : 1;
            $stateId = !empty($data['state_id']) ? (int) $data['state_id'] : 1;
            $cityId = !empty($data['city_id']) ? (int) $data['city_id'] : 1;
            $zipCodeId = !empty($data['zip_code_id']) ? (int) $data['zip_code_id'] : null;

            $slug = !empty($listing?->slug) ? $listing->slug : (Str::slug($title) ?: 'draft') . '-' . Str::random(8);

            $payload = [
                'user_id' => $user->id,
                'category_id' => $categoryId,
                'state_id' => $stateId,
                'city_id' => $cityId,
                'zip_code_id' => $zipCodeId,
                'title' => $title,
                'slug' => $slug,
                'description' => $description,
                'poster_age' => !empty($data['poster_age']) ? (int) $data['poster_age'] : 25,
                'poster_gender' => $data['poster_gender'] ?? 'Not specified',
                'intent' => $data['intent'] ?? 'dating',
                'status' => Listing::STATUS_DRAFT,
                'is_autosaved' => (bool) ($data['is_autosaved'] ?? false),
            ];

            if ($listing) {
                $listing->update($payload);
            } else {
                $listing = Listing::create($payload);
            }

            $listing->content_hash = $listing->computeContentHash();
            $listing->save();

            // Save Location record
            $this->saveListingLocation($listing, $data);

            // Save Contact record
            $this->saveListingContact($listing, $data);

            // Save Dynamic Attributes if provided
            if (!empty($data['attributes']) && is_array($data['attributes'])) {
                $this->saveListingAttributes($listing, $data['attributes']);
            }

            return $listing;
        });
    }

    /**
     * Submit listing for moderation review.
     *
     * @param Listing $listing
     * @param User $user
     * @param array<string, mixed> $submissionData
     * @return Listing
     */
    public function submitForReview(Listing $listing, User $user, array $submissionData = []): Listing
    {
        return DB::transaction(function () use ($listing, $user, $submissionData) {
            // Merge updated data if supplied in final submission step
            if (!empty($submissionData)) {
                $this->updateListingFields($listing, $submissionData);
            }

            // 1. Age Verification Check
            $minAge = config('localdatehub.safety.min_age', 18);
            if ($listing->poster_age < $minAge) {
                throw new AgeVerificationException("Listing poster must be at least {$minAge} years old.");
            }

            // 2. Prohibited Content Verification
            $this->validateContentCompliance($listing->title, $listing->description);

            // 3. Duplicate Listing Detection
            $duplicate = $this->detectDuplicate($listing->title, $listing->description, $listing->id);
            if ($duplicate) {
                throw new InvalidArgumentException(
                    "A duplicate listing with identical or near-identical text was recently posted in '{$duplicate->city?->name}'. Please author a unique listing."
                );
            }

            // 4. Validate Category Attributes
            if ($listing->category) {
                $attributesData = $submissionData['attributes'] ?? [];
                if (empty($attributesData)) {
                    $attributesData = $listing->attributes->pluck('value_text', 'attribute_code')->all();
                }
                $this->categoryService->validateListingAttributes($listing->category, $attributesData);
            }

            // 5. Update Status to pending_review
            $previousStatus = $listing->status;
            $listing->status = Listing::STATUS_PENDING_REVIEW;
            $listing->rejection_reason = null;
            $listing->content_hash = $listing->computeContentHash();
            $listing->save();

            $listing->recordStatusChange(
                Listing::STATUS_PENDING_REVIEW,
                "Listing submitted by poster for moderation review (previous status: {$previousStatus}).",
                $user->id
            );

            return $listing;
        });
    }

    /**
     * Moderate and approve listing.
     *
     * @param Listing $listing
     * @param User|null $moderator
     * @return Listing
     */
    public function approveListing(Listing $listing, ?User $moderator = null): Listing
    {
        return DB::transaction(function () use ($listing, $moderator) {
            $listing->status = Listing::STATUS_ACTIVE;
            $listing->rejection_reason = null;
            $listing->published_at = $listing->published_at ?: now();
            $listing->expires_at = now()->addDays(30);
            $listing->save();

            // Approve any pending media
            foreach ($listing->media as $media) {
                if ($media->moderation_status === 'pending') {
                    $this->mediaService->approveMedia($media);
                }
            }

            $listing->recordStatusChange(
                Listing::STATUS_ACTIVE,
                'Approved by moderator and published to search index.',
                $moderator?->id
            );

            return $listing;
        });
    }

    /**
     * Moderate and reject listing with feedback.
     *
     * @param Listing $listing
     * @param string $reason
     * @param User|null $moderator
     * @return Listing
     */
    public function rejectListing(Listing $listing, string $reason, ?User $moderator = null): Listing
    {
        return DB::transaction(function () use ($listing, $reason, $moderator) {
            $listing->status = Listing::STATUS_REJECTED;
            $listing->rejection_reason = $reason;
            $listing->rejection_count += 1;
            $listing->save();

            $listing->recordStatusChange(
                Listing::STATUS_REJECTED,
                $reason,
                $moderator?->id
            );

            return $listing;
        });
    }

    /**
     * Renew active or expired listing for an additional 30 days.
     *
     * @param Listing $listing
     * @param User $user
     * @return Listing
     */
    public function renewListing(Listing $listing, User $user): Listing
    {
        if (!$listing->canBeRenewed()) {
            throw new RuntimeException("Only active or expired listings can be renewed.");
        }

        return DB::transaction(function () use ($listing, $user) {
            $listing->status = Listing::STATUS_ACTIVE;
            $listing->expires_at = now()->addDays(30);
            $listing->renewed_at = now();
            $listing->save();

            $listing->recordStatusChange(
                Listing::STATUS_ACTIVE,
                'Renewed by poster for 30 days.',
                $user->id
            );

            return $listing;
        });
    }

    /**
     * Pause an active listing.
     */
    public function pauseListing(Listing $listing, User $user): Listing
    {
        return DB::transaction(function () use ($listing, $user) {
            $listing->status = Listing::STATUS_PAUSED;
            $listing->save();

            $listing->recordStatusChange(
                Listing::STATUS_PAUSED,
                'Paused by listing owner.',
                $user->id
            );

            return $listing;
        });
    }

    /**
     * Resume a paused listing.
     */
    public function resumeListing(Listing $listing, User $user): Listing
    {
        return DB::transaction(function () use ($listing, $user) {
            $listing->status = Listing::STATUS_ACTIVE;
            $listing->save();

            $listing->recordStatusChange(
                Listing::STATUS_ACTIVE,
                'Resumed by listing owner.',
                $user->id
            );

            return $listing;
        });
    }

    /**
     * Suspend a listing for safety, compliance, or terms violation.
     */
    public function suspendListing(Listing $listing, string $reason, ?User $moderator = null): Listing
    {
        return DB::transaction(function () use ($listing, $reason, $moderator) {
            $listing->status = Listing::STATUS_SUSPENDED;
            $listing->rejection_reason = $reason;
            $listing->save();

            $listing->recordStatusChange(
                Listing::STATUS_SUSPENDED,
                $reason,
                $moderator?->id
            );

            return $listing;
        });
    }

    /**
     * Soft delete listing.
     */
    public function deleteListing(Listing $listing, User $user, ?string $reason = null): bool
    {
        return DB::transaction(function () use ($listing, $user, $reason) {
            $listing->recordStatusChange(
                'deleted',
                $reason ?: 'Deleted by user.',
                $user->id
            );

            return (bool) $listing->delete();
        });
    }

    /**
     * Update listing fields and record revision snapshot.
     */
    public function updateListing(Listing $listing, array $data, ?User $actor = null): Listing
    {
        return DB::transaction(function () use ($listing, $data, $actor) {
            // Snapshot previous state into revisions
            $listing->recordRevision('Poster edited listing details.', $actor?->id);

            $this->updateListingFields($listing, $data);

            if (!empty($data['attributes']) && is_array($data['attributes'])) {
                $this->saveListingAttributes($listing, $data['attributes']);
            }

            if (!empty($data['location'])) {
                $this->saveListingLocation($listing, $data['location']);
            }

            if (!empty($data['contact'])) {
                $this->saveListingContact($listing, $data['contact']);
            }

            $listing->content_hash = $listing->computeContentHash();
            $listing->save();

            return $listing;
        });
    }

    /**
     * Update direct listing table columns.
     */
    protected function updateListingFields(Listing $listing, array $data): void
    {
        if (isset($data['title'])) {
            $listing->title = trim($data['title']);
        }
        if (isset($data['description'])) {
            $listing->description = trim($data['description']);
        }
        if (isset($data['poster_age'])) {
            $listing->poster_age = (int) $data['poster_age'];
        }
        if (isset($data['poster_gender'])) {
            $listing->poster_gender = $data['poster_gender'];
        }
        if (isset($data['intent'])) {
            $listing->intent = $data['intent'];
        }
        if (isset($data['category_id'])) {
            $listing->category_id = (int) $data['category_id'];
        }
        if (isset($data['state_id'])) {
            $listing->state_id = (int) $data['state_id'];
        }
        if (isset($data['city_id'])) {
            $listing->city_id = (int) $data['city_id'];
        }
        if (array_key_exists('zip_code_id', $data)) {
            $listing->zip_code_id = !empty($data['zip_code_id']) ? (int) $data['zip_code_id'] : null;
        }

        $listing->save();
    }

    /**
     * Persist location details and anti-triangulation coordinates.
     */
    protected function saveListingLocation(Listing $listing, array $data): ListingLocation
    {
        $city = City::find($listing->city_id);
        $rawLat = (float) ($data['latitude'] ?? ($city?->latitude ?: 30.2672));
        $rawLon = (float) ($data['longitude'] ?? ($city?->longitude ?: -97.7431));

        // Anti-triangulation fuzzing via LocationService
        $coord = new CoordinateDTO(latitude: $rawLat, longitude: $rawLon, cityName: $city?->name ?: '');
        $fuzzed = $this->locationService->fuzzCoordinates($coord);

        return ListingLocation::updateOrCreate(
            ['listing_id' => $listing->id],
            [
                'neighborhood' => $data['neighborhood'] ?? null,
                'cross_streets' => $data['cross_streets'] ?? null,
                'postal_code' => $data['postal_code'] ?? null,
                'latitude' => $rawLat,
                'longitude' => $rawLon,
                'fuzzed_latitude' => $fuzzed->latitude,
                'fuzzed_longitude' => $fuzzed->longitude,
                'hide_exact_distance' => (bool) ($data['hide_exact_distance'] ?? false),
            ]
        );
    }

    /**
     * Persist contact preferences and encrypted channels.
     */
    protected function saveListingContact(Listing $listing, array $data): ListingContact
    {
        $maskedRelay = 'relay-' . Str::random(10) . '@localadzone.com';

        return ListingContact::updateOrCreate(
            ['listing_id' => $listing->id],
            [
                'allow_platform_messages' => (bool) ($data['allow_platform_messages'] ?? true),
                'allow_email_relay' => (bool) ($data['allow_email_relay'] ?? true),
                'masked_relay_email' => $data['masked_relay_email'] ?? $maskedRelay,
                'preferred_phone_encrypted' => !empty($data['preferred_phone']) ? $data['preferred_phone'] : null,
                'external_handle_encrypted' => !empty($data['external_handle']) ? $data['external_handle'] : null,
                'show_phone' => (bool) ($data['show_phone'] ?? false),
                'preferred_contact_method' => $data['preferred_contact_method'] ?? 'platform_message',
                'contact_instructions' => $data['contact_instructions'] ?? null,
            ]
        );
    }

    /**
     * Persist dynamic category attribute values.
     *
     * @param Listing $listing
     * @param array<string, mixed> $attributes
     */
    protected function saveListingAttributes(Listing $listing, array $attributes): void
    {
        if (!$listing->category) {
            return;
        }

        $allCategoryAttrs = $listing->category->allAttributes()->keyBy('code');

        foreach ($attributes as $code => $value) {
            $catAttr = $allCategoryAttrs->get($code);
            if (!$catAttr) {
                continue;
            }

            $optionId = null;
            $textVal = is_string($value) ? $value : null;
            $numVal = is_numeric($value) ? (float) $value : null;
            $jsonVal = is_array($value) ? $value : null;

            if (in_array($catAttr->field_type, ['select', 'radio'])) {
                $option = $catAttr->options->firstWhere('value', $value);
                $optionId = $option?->id;
                $textVal = $option?->label ?: (string) $value;
            }

            ListingAttribute::updateOrCreate(
                ['listing_id' => $listing->id, 'attribute_code' => $code],
                [
                    'category_attribute_id' => $catAttr->id,
                    'category_attribute_option_id' => $optionId,
                    'value_text' => $textVal,
                    'value_number' => $numVal,
                    'value_json' => $jsonVal,
                ]
            );
        }
    }
}

