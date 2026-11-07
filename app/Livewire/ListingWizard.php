<?php

namespace App\Livewire;

use App\Modules\Categories\Models\Category;
use App\Modules\Listings\Models\Listing;
use App\Modules\Listings\Models\ListingMedia;
use App\Modules\Listings\Services\ListingManagementService;
use App\Modules\Listings\Services\ListingMediaService;
use App\Modules\Locations\Models\City;
use App\Modules\Locations\Models\State;
use App\Modules\Locations\Models\ZipCode;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;

class ListingWizard extends Component
{
    use WithFileUploads;

    public ?int $listingId = null;
    public int $currentStep = 1;
    public int $totalSteps = 12;

    // Step 1 & 2: Category
    public ?int $selectedRootId = null;
    public ?int $selectedGroupId = null;
    public ?int $selectedCategoryId = null;

    // Step 3: Basic details
    public string $title = '';
    public int $poster_age = 25;
    public string $poster_gender = 'Male';
    public string $intent = 'dating';

    // Step 4: Description
    public string $description = '';

    // Step 5: Dynamic Attributes
    public array $dynamicAttributes = [];

    // Step 6: Location
    public ?int $state_id = null;
    public ?int $city_id = null;
    public ?int $zip_code_id = null;
    public string $neighborhood = '';
    public string $cross_streets = '';
    public bool $hide_exact_distance = false;

    // Step 7: Photos
    public $newPhotos = [];
    public array $uploadedMedia = [];

    // Step 8 & 9: Contact Preferences
    public bool $allow_platform_messages = true;
    public bool $allow_email_relay = true;
    public string $preferred_phone = '';
    public string $external_handle = '';
    public bool $show_phone = false;
    public string $contact_instructions = '';

    // Step 11: Policy Confirmation
    public bool $confirm_age_18 = false;
    public bool $confirm_community_rules = false;
    public bool $confirm_anti_trafficking = false;

    // Status messaging
    public string $autosaveStatus = '';
    public string $errorMessage = '';

    public function mount(?int $listingId = null): void
    {
        $user = Auth::user();
        if ($user && $user->posterProfile) {
            $this->poster_age = $user->posterProfile->age ?: 25;
            $this->poster_gender = $user->posterProfile->gender ?: 'Male';
        }

        if ($listingId) {
            $this->loadExistingListing($listingId);
        } else {
            // Default to Personals root and first group
            $root = Category::whereNull('parent_id')->first();
            if ($root) {
                $this->selectedRootId = $root->id;
                $firstGroup = $root->children()->first();
                if ($firstGroup) {
                    $this->selectedGroupId = $firstGroup->id;
                }
            }

            // Default location to TX / Austin if available
            $defaultState = State::where('code', 'TX')->first() ?: State::first();
            if ($defaultState) {
                $this->state_id = $defaultState->id;
                $defaultCity = City::where('state_id', $defaultState->id)->first();
                if ($defaultCity) {
                    $this->city_id = $defaultCity->id;
                }
            }
        }
    }

    public function loadExistingListing(int $listingId): void
    {
        $listing = Listing::with(['category.parent', 'location', 'contact', 'attributes', 'media'])
            ->where('id', $listingId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $this->listingId = $listing->id;
        $this->selectedCategoryId = $listing->category_id;
        if ($listing->category) {
            $this->selectedGroupId = $listing->category->parent_id;
            $this->selectedRootId = $listing->category->parent?->parent_id ?: $listing->category->parent_id;
        }

        $this->title = $listing->title;
        $this->description = $listing->description;
        $this->poster_age = $listing->poster_age;
        $this->poster_gender = $listing->poster_gender ?: 'Not specified';
        $this->intent = $listing->intent ?: 'dating';

        $this->state_id = $listing->state_id;
        $this->city_id = $listing->city_id;
        $this->zip_code_id = $listing->zip_code_id;

        if ($listing->location) {
            $this->neighborhood = $listing->location->neighborhood ?: '';
            $this->cross_streets = $listing->location->cross_streets ?: '';
            $this->hide_exact_distance = $listing->location->hide_exact_distance;
        }

        if ($listing->contact) {
            $this->allow_platform_messages = $listing->contact->allow_platform_messages;
            $this->allow_email_relay = $listing->contact->allow_email_relay;
            $this->preferred_phone = $listing->contact->preferred_phone_encrypted ?: '';
            $this->external_handle = $listing->contact->external_handle_encrypted ?: '';
            $this->show_phone = $listing->contact->show_phone;
            $this->contact_instructions = $listing->contact->contact_instructions ?: '';
        }

        foreach ($listing->attributes as $attr) {
            $this->dynamicAttributes[$attr->attribute_code] = $attr->value_text ?: $attr->value_number;
        }

        $this->uploadedMedia = $listing->media->map(fn ($m) => [
            'id' => $m->id,
            'url' => asset('storage/' . $m->file_path),
            'thumb' => asset('storage/' . ($m->thumbnail_path ?: $m->file_path)),
            'is_primary' => $m->is_primary,
            'status' => $m->moderation_status,
        ])->all();
    }

    public function updatedSelectedGroupId(): void
    {
        $this->selectedCategoryId = null;
    }

    public function updatedStateId(): void
    {
        $firstCity = City::where('state_id', $this->state_id)->first();
        $this->city_id = $firstCity?->id;
        $this->zip_code_id = null;
    }

    public function updatedNewPhotos(): void
    {
        $this->validate([
            'newPhotos.*' => 'image|max:5120|mimes:jpeg,png,webp',
        ]);

        $this->autosave(false);

        if (!$this->listingId) {
            $this->saveDraft();
        }

        $listing = Listing::find($this->listingId);
        if ($listing) {
            $mediaService = app(ListingMediaService::class);
            foreach ($this->newPhotos as $photo) {
                try {
                    $mediaService->processAndStorePhoto($listing, $photo);
                } catch (\Exception $e) {
                    $this->errorMessage = $e->getMessage();
                }
            }

            $this->newPhotos = [];
            $this->loadMediaFromDb($listing);
        }
    }

    public function setPrimaryPhoto(int $mediaId): void
    {
        if (!$this->listingId) return;

        ListingMedia::where('listing_id', $this->listingId)->update(['is_primary' => false]);
        ListingMedia::where('id', $mediaId)->where('listing_id', $this->listingId)->update(['is_primary' => true]);

        $listing = Listing::find($this->listingId);
        if ($listing) {
            $this->loadMediaFromDb($listing);
        }
    }

    public function removePhoto(int $mediaId): void
    {
        $media = ListingMedia::where('id', $mediaId)
            ->where('listing_id', $this->listingId)
            ->first();

        if ($media) {
            app(ListingMediaService::class)->deleteMedia($media);
            $listing = Listing::find($this->listingId);
            if ($listing) {
                $this->loadMediaFromDb($listing);
            }
        }
    }

    protected function loadMediaFromDb(Listing $listing): void
    {
        $this->uploadedMedia = $listing->media()->get()->map(fn ($m) => [
            'id' => $m->id,
            'url' => asset('storage/' . $m->file_path),
            'thumb' => asset('storage/' . ($m->thumbnail_path ?: $m->file_path)),
            'is_primary' => $m->is_primary,
            'status' => $m->moderation_status,
        ])->all();
    }

    public function nextStep(): void
    {
        $this->validateCurrentStep();
        $this->autosave();

        if ($this->currentStep < $this->totalSteps) {
            $this->currentStep++;
        }
    }

    public function previousStep(): void
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }

    public function goToStep(int $step): void
    {
        if ($step < $this->currentStep) {
            $this->currentStep = $step;
        } else {
            $this->validateCurrentStep();
            $this->currentStep = min($step, $this->totalSteps);
        }
    }

    public function validateCurrentStep(): void
    {
        $this->errorMessage = '';

        if ($this->currentStep === 1) {
            $this->validate([
                'selectedGroupId' => 'required|integer|exists:categories,id',
            ]);
        } elseif ($this->currentStep === 2) {
            $this->validate([
                'selectedCategoryId' => 'required|integer|exists:categories,id',
            ]);
        } elseif ($this->currentStep === 3) {
            $this->validate([
                'title' => 'required|string|min:5|max:120',
                'poster_age' => 'required|integer|min:18|max:99',
                'poster_gender' => 'required|string|max:50',
                'intent' => 'required|string',
            ]);
        } elseif ($this->currentStep === 4) {
            $this->validate([
                'description' => 'required|string|min:20|max:5000',
            ]);
            // Prohibited content check
            app(ListingManagementService::class)->validateContentCompliance($this->title, $this->description);
        } elseif ($this->currentStep === 6) {
            $this->validate([
                'state_id' => 'required|integer|exists:states,id',
                'city_id' => 'required|integer|exists:cities,id',
            ]);
        } elseif ($this->currentStep === 11) {
            $this->validate([
                'confirm_age_18' => 'accepted',
                'confirm_community_rules' => 'accepted',
                'confirm_anti_trafficking' => 'accepted',
            ], [
                'confirm_age_18.accepted' => 'You must verify that you are at least 18 years of age.',
                'confirm_community_rules.accepted' => 'You must agree to our community standards.',
                'confirm_anti_trafficking.accepted' => 'You must acknowledge that commercial sexual services and solicitation are strictly prohibited.',
            ]);
        }
    }

    public function autosave(bool $showNotification = true): void
    {
        if (!Auth::check() || empty($this->title)) {
            return;
        }

        try {
            $service = app(ListingManagementService::class);
            $listing = $service->saveDraft(Auth::user(), $this->gatherFormData(true), $this->listingId);
            $this->listingId = $listing->id;
            if ($showNotification) {
                $this->autosaveStatus = 'Draft autosaved at ' . now()->format('H:i:s');
            }
        } catch (\Exception $e) {
            // Autosave silent failure to not disrupt typing
        }
    }

    public function saveDraft(): void
    {
        $service = app(ListingManagementService::class);
        $listing = $service->saveDraft(Auth::user(), $this->gatherFormData(false), $this->listingId);
        $this->listingId = $listing->id;
        $this->autosaveStatus = 'Draft saved successfully.';
    }

    public function submitForReview(): mixed
    {
        $this->validate([
            'confirm_age_18' => 'accepted',
            'confirm_community_rules' => 'accepted',
            'confirm_anti_trafficking' => 'accepted',
        ]);

        $service = app(ListingManagementService::class);
        $formData = $this->gatherFormData(false);

        // Ensure draft exists first
        $listing = $service->saveDraft(Auth::user(), $formData, $this->listingId);
        $this->listingId = $listing->id;

        try {
            $service->submitForReview($listing, Auth::user(), $formData);
            session()->flash('status', "Your listing '{$listing->title}' has been submitted for moderation review.");
            return redirect()->route('account.listings.index');
        } catch (\Exception $e) {
            $this->errorMessage = $e->getMessage();
            $this->currentStep = 4; // Return to description or relevant step
            return null;
        }
    }

    protected function gatherFormData(bool $isAutosave = false): array
    {
        $attributes = $this->dynamicAttributes;
        if (!isset($attributes['intent']) && !empty($this->intent)) {
            $attributes['intent'] = $this->intent;
        }

        return [
            'category_id' => $this->selectedCategoryId ?: $this->selectedGroupId,
            'title' => $this->title,
            'description' => $this->description,
            'poster_age' => $this->poster_age,
            'poster_gender' => $this->poster_gender,
            'intent' => $this->intent,
            'state_id' => $this->state_id,
            'city_id' => $this->city_id,
            'zip_code_id' => $this->zip_code_id,
            'neighborhood' => $this->neighborhood,
            'cross_streets' => $this->cross_streets,
            'hide_exact_distance' => $this->hide_exact_distance,
            'allow_platform_messages' => $this->allow_platform_messages,
            'allow_email_relay' => $this->allow_email_relay,
            'preferred_phone' => $this->preferred_phone,
            'external_handle' => $this->external_handle,
            'show_phone' => $this->show_phone,
            'contact_instructions' => $this->contact_instructions,
            'attributes' => $attributes,
            'is_autosaved' => $isAutosave,
        ];
    }

    public function render()
    {
        $root = Category::whereNull('parent_id')->first();
        $categoryGroups = $root ? $root->children()->where('is_active', true)->orderBy('display_order')->get() : collect();
        $subcategories = $this->selectedGroupId
            ? Category::where('parent_id', $this->selectedGroupId)->where('is_active', true)->orderBy('display_order')->get()
            : collect();

        $selectedCategory = $this->selectedCategoryId ? Category::find($this->selectedCategoryId) : null;
        $categoryAttributes = $selectedCategory ? $selectedCategory->allAttributes() : collect();

        $states = State::orderBy('name')->get();
        $cities = $this->state_id ? City::where('state_id', $this->state_id)->orderBy('name')->get() : collect();
        $zipCodes = $this->city_id ? ZipCode::where('city_id', $this->city_id)->orderBy('code')->get() : collect();

        return view('livewire.listing-wizard', [
            'categoryGroups' => $categoryGroups,
            'subcategories' => $subcategories,
            'selectedCategory' => $selectedCategory,
            'categoryAttributes' => $categoryAttributes,
            'states' => $states,
            'cities' => $cities,
            'zipCodes' => $zipCodes,
        ]);
    }
}
