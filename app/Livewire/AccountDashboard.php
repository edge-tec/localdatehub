<?php

namespace App\Livewire;

use App\Models\User;
use App\Modules\Accounts\Models\AgeAssurance;
use App\Modules\Accounts\Models\UserBlock;
use App\Modules\Accounts\Models\UserPreference;
use App\Modules\Accounts\Models\UserProfile;
use App\Modules\Billing\Models\FeaturedListing;
use App\Modules\Billing\Models\PaymentTransaction;
use App\Modules\Billing\Models\PricingPlan;
use App\Modules\Billing\Models\Subscription;
use App\Modules\Billing\Services\PromotionService;
use App\Modules\Billing\Services\SubscriptionService;
use App\Modules\Categories\Models\Category;
use App\Modules\Contact\Models\ContactMessage;
use App\Modules\Listings\Models\Listing;
use App\Modules\Listings\Models\ListingAnalytics;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class AccountDashboard extends Component
{
    use WithPagination, WithFileUploads;

    #[Url(as: 'tab')]
    public string $tab = 'overview';

    // Listing Filter & Search Properties
    public string $listingSearch = '';
    public string $listingStatus = 'all'; // all, active, pending_review, rejected, expired, suspended, paused, draft
    public ?int $listingCategory = null;
    public string $listingSort = 'newest'; // newest, views, expires
    public array $selectedListings = [];
    public bool $selectAllListings = false;
    public ?int $listingToDeleteId = null;
    public bool $showDeleteListingModal = false;

    // Saved Listings Properties
    public string $savedSearch = '';

    // Contact Messages Advanced Properties
    public string $messageFolder = 'inbox'; // inbox, sent
    public string $messageFilter = 'all'; // all, unread, replied, flagged
    public string $messageSearch = '';
    public ?int $messageListingFilter = null;
    public ?ContactMessage $inspectedMessage = null;
    public bool $showMessageModal = false;
    public string $replyMessageContent = '';
    public array $conversationReplies = [];
    public array $selectedMessages = [];
    public bool $selectAllMessages = false;

    // Analytics Properties
    public string $analyticsTimeframe = '30d'; // 7d, 30d, all

    // Promotions & Featured Ads
    public ?int $promoteListingId = null;
    public ?int $selectedPlanId = null;
    public string $selectedPlacement = 'category_top'; // homepage, city_top, category_top
    public bool $showPromoteModal = false;

    // Profile Settings Properties
    public string $displayName = '';
    public string $bio = '';
    public string $gender = '';
    public string $seeking = '';
    public string $city = '';
    public string $stateCode = '';
    public string $zipCode = '';
    public $avatar = null;

    // Privacy Settings Properties
    public bool $hideLocationDetails = false;
    public bool $hideAge = false;
    public bool $allowContactMessages = true;
    public bool $requireVerifiedSenders = false;

    // Age Assurance Status
    public ?string $declaredBirthdate = null;
    public bool $ageAffirmation = false;

    // Security Settings
    public string $currentPassword = '';
    public string $newPassword = '';
    public string $newPasswordConfirmation = '';

    // Blocked Users
    public string $blockUsername = '';
    public string $blockReason = '';

    // Account Deletion
    public string $deleteAccountPassword = '';
    public bool $showDeleteAccountModal = false;

    public function mount(string $initialTab = 'overview'): void
    {
        if (request()->has('tab')) {
            $this->tab = request()->get('tab');
        } elseif ($initialTab) {
            $this->tab = $initialTab;
        }

        $this->loadUserProfile();
    }

    public function setTab(string $newTab): void
    {
        $this->tab = $newTab;
        $this->resetPage();
    }

    public function updatedListingSearch(): void
    {
        $this->resetPage();
    }

    public function updatedListingStatus(): void
    {
        $this->resetPage();
        $this->selectedListings = [];
        $this->selectAllListings = false;
    }

    public function updatedSelectAllListings($value): void
    {
        if ($value) {
            $this->selectedListings = $this->getMyListingsQuery()->pluck('id')->map(fn($id) => (string) $id)->toArray();
        } else {
            $this->selectedListings = [];
        }
    }

    public function loadUserProfile(): void
    {
        /** @var User $user */
        $user = Auth::user();
        $profile = $user->profile;
        $preferences = $user->preferences;

        if ($profile) {
            $this->displayName = $profile->display_name ?? $user->name;
            $this->bio = $profile->bio ?? '';
            $this->gender = $profile->gender ?? '';
            $this->seeking = $profile->seeking ?? '';
            $this->city = $profile->city ?? '';
            $this->stateCode = $profile->state_code ?? '';
            $this->zipCode = $profile->zip_code ?? '';
            $this->hideLocationDetails = (bool) $profile->hide_location_details;
            $this->hideAge = (bool) $profile->hide_age;
        } else {
            $this->displayName = $user->name;
        }

        if ($preferences) {
            $this->allowContactMessages = (bool) $preferences->allow_contact_messages;
            $this->requireVerifiedSenders = (bool) $preferences->require_verified_senders;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Listing Operations (My Listings, Active, Pending, Expired, Suspended)
    |--------------------------------------------------------------------------
    */

    public function renewListing(int $listingId): void
    {
        $listing = Listing::where('user_id', Auth::id())->findOrFail($listingId);
        $listing->expires_at = now()->addDays(30);
        $listing->status = Listing::STATUS_ACTIVE;
        $listing->save();

        $listing->recordStatusChange(Listing::STATUS_ACTIVE, 'Renewed by advertiser (+30 days).', Auth::id());
        session()->flash('success', "Listing [{$listing->title}] has been successfully renewed for 30 days.");
    }

    public function pauseListing(int $listingId): void
    {
        $listing = Listing::where('user_id', Auth::id())->findOrFail($listingId);
        $listing->status = Listing::STATUS_PAUSED;
        $listing->save();

        $listing->recordStatusChange(Listing::STATUS_PAUSED, 'Paused by advertiser.', Auth::id());
        session()->flash('success', "Listing [{$listing->title}] has been paused.");
    }

    public function resumeListing(int $listingId): void
    {
        $listing = Listing::where('user_id', Auth::id())->findOrFail($listingId);
        $listing->status = Listing::STATUS_ACTIVE;
        $listing->save();

        $listing->recordStatusChange(Listing::STATUS_ACTIVE, 'Resumed by advertiser.', Auth::id());
        session()->flash('success', "Listing [{$listing->title}] is now active.");
    }

    public function confirmDeleteListing(int $listingId): void
    {
        $this->listingToDeleteId = $listingId;
        $this->showDeleteListingModal = true;
    }

    public function deleteListing(): void
    {
        if (!$this->listingToDeleteId) {
            return;
        }

        $listing = Listing::where('user_id', Auth::id())->findOrFail($this->listingToDeleteId);
        $title = $listing->title;
        $listing->delete();

        $this->showDeleteListingModal = false;
        $this->listingToDeleteId = null;
        session()->flash('success', "Listing [{$title}] was deleted.");
    }

    public function bulkPauseListings(): void
    {
        if (empty($this->selectedListings)) {
            return;
        }

        Listing::where('user_id', Auth::id())
            ->whereIn('id', $this->selectedListings)
            ->update(['status' => Listing::STATUS_PAUSED]);

        $this->selectedListings = [];
        $this->selectAllListings = false;
        session()->flash('success', 'Selected listings have been paused.');
    }

    public function bulkResumeListings(): void
    {
        if (empty($this->selectedListings)) {
            return;
        }

        Listing::where('user_id', Auth::id())
            ->whereIn('id', $this->selectedListings)
            ->update(['status' => Listing::STATUS_ACTIVE]);

        $this->selectedListings = [];
        $this->selectAllListings = false;
        session()->flash('success', 'Selected listings have been activated.');
    }

    public function bulkRenewListings(): void
    {
        if (empty($this->selectedListings)) {
            return;
        }

        Listing::where('user_id', Auth::id())
            ->whereIn('id', $this->selectedListings)
            ->update([
                'status' => Listing::STATUS_ACTIVE,
                'expires_at' => now()->addDays(30),
            ]);

        $this->selectedListings = [];
        $this->selectAllListings = false;
        session()->flash('success', 'Selected listings have been renewed for 30 days.');
    }

    public function bulkDeleteListings(): void
    {
        if (empty($this->selectedListings)) {
            return;
        }

        Listing::where('user_id', Auth::id())
            ->whereIn('id', $this->selectedListings)
            ->delete();

        $this->selectedListings = [];
        $this->selectAllListings = false;
        session()->flash('success', 'Selected listings have been deleted.');
    }

    /*
    |--------------------------------------------------------------------------
    | Saved / Favorited Listings Operations
    |--------------------------------------------------------------------------
    */

    public function removeFavorite(int $listingId): void
    {
        /** @var User $user */
        $user = Auth::user();
        $user->favorites()->detach($listingId);

        $listing = Listing::find($listingId);
        if ($listing && $listing->favorite_count > 0) {
            $listing->decrement('favorite_count');
        }

        session()->flash('success', 'Listing removed from your saved favorites.');
    }

    /*
    |--------------------------------------------------------------------------
    | Contact Message Operations
    |--------------------------------------------------------------------------
    */

    public function inspectMessage(int $messageId): void
    {
        $message = ContactMessage::where(function ($q) {
                $q->where('recipient_id', Auth::id())
                  ->orWhere('sender_id', Auth::id());
            })
            ->with(['listing.city.state', 'listing.primaryPhoto', 'sender', 'recipient'])
            ->findOrFail($messageId);

        if ($message->recipient_id === Auth::id() && !$message->is_read) {
            $message->is_read = true;
            $message->read_at = now();
            $message->save();
        }

        $this->inspectedMessage = $message;
        $this->loadConversationThread($message);
        $this->replyMessageContent = '';
        $this->showMessageModal = true;
    }

    public function loadConversationThread(ContactMessage $message): void
    {
        if ($message->listing_id) {
            $senderId = $message->sender_id;
            $recipientId = $message->recipient_id;

            $replies = ContactMessage::where('listing_id', $message->listing_id)
                ->where('id', '!=', $message->id)
                ->where(function ($q) use ($senderId, $recipientId, $message) {
                    if ($senderId && $recipientId) {
                        $q->where(function ($sub) use ($senderId, $recipientId) {
                            $sub->where('sender_id', $senderId)->where('recipient_id', $recipientId);
                        })->orWhere(function ($sub) use ($senderId, $recipientId) {
                            $sub->where('sender_id', $recipientId)->where('recipient_id', $senderId);
                        });
                    } elseif (!empty($message->sender_email_encrypted)) {
                        $q->where('sender_email_encrypted', $message->sender_email_encrypted);
                    }
                })
                ->orderBy('created_at', 'asc')
                ->get();

            $currentUserId = Auth::id();
            $currentUserEmail = Auth::user()?->email;

            $this->conversationReplies = $replies->map(fn($r) => [
                'id' => $r->id,
                'sender_id' => $r->sender_id,
                'sender_name' => $r->sender_name,
                'sender_email' => $r->sender_email_encrypted,
                'message' => $r->message_encrypted,
                'created_at_formatted' => $r->created_at ? $r->created_at->format('M d, g:i A') : '',
                'is_me' => ($r->sender_id === $currentUserId || ($currentUserEmail && $r->sender_email_encrypted === $currentUserEmail)),
            ])->all();
        } else {
            $this->conversationReplies = [];
        }
    }

    public function sendDirectReply(): void
    {
        $this->validate([
            'replyMessageContent' => ['required', 'string', 'min:3', 'max:2500'],
        ]);

        if (!$this->inspectedMessage) {
            return;
        }

        try {
            app(\App\Modules\Contact\Services\ContactService::class)
                ->validateMessageSafety($this->replyMessageContent);
        } catch (\Exception $e) {
            $this->addError('replyMessageContent', $e->getMessage());
            return;
        }

        $currentUser = Auth::user();
        $targetRecipientId = $this->inspectedMessage->recipient_id === $currentUser->id 
            ? $this->inspectedMessage->sender_id 
            : $this->inspectedMessage->recipient_id;

        $reply = ContactMessage::create([
            'listing_id' => $this->inspectedMessage->listing_id,
            'sender_id' => $currentUser->id,
            'recipient_id' => $targetRecipientId,
            'inquiry_type' => 'listing_inquiry',
            'sender_name' => $currentUser->profile?->display_name ?: $currentUser->name,
            'sender_email_encrypted' => $currentUser->email,
            'sender_phone_encrypted' => $currentUser->profile?->phone ?? null,
            'subject' => 'Re: ' . ltrim(str_replace('Re:', '', $this->inspectedMessage->subject ?? 'Inquiry')),
            'message_encrypted' => $this->replyMessageContent,
            'is_read' => false,
            'is_replied' => true,
            'is_flagged' => false,
            'client_ip' => request()->ip(),
            'user_agent' => substr((string) request()->userAgent(), 0, 255),
        ]);

        $this->inspectedMessage->is_replied = true;
        $this->inspectedMessage->save();

        $this->replyMessageContent = '';
        $this->loadConversationThread($this->inspectedMessage);

        session()->flash('success', 'Your protected reply has been safely delivered.');
    }

    public function insertQuickReply(string $template): void
    {
        $this->replyMessageContent = $template;
    }

    public function markMessageUnread(int $messageId): void
    {
        $message = ContactMessage::where('recipient_id', Auth::id())->findOrFail($messageId);
        $message->is_read = false;
        $message->read_at = null;
        $message->save();

        if ($this->inspectedMessage && $this->inspectedMessage->id === $messageId) {
            $this->closeMessageModal();
        }

        session()->flash('success', 'Inquiry marked as unread.');
    }

    public function closeMessageModal(): void
    {
        $this->showMessageModal = false;
        $this->inspectedMessage = null;
        $this->conversationReplies = [];
        $this->replyMessageContent = '';
    }

    public function markMessageReplied(int $messageId): void
    {
        $message = ContactMessage::where('recipient_id', Auth::id())->findOrFail($messageId);
        $message->is_replied = true;
        $message->save();

        if ($this->inspectedMessage && $this->inspectedMessage->id === $messageId) {
            $this->inspectedMessage->is_replied = true;
        }

        session()->flash('success', 'Inquiry marked as replied.');
    }

    public function flagMessage(int $messageId, string $reason = 'spam'): void
    {
        $message = ContactMessage::where('recipient_id', Auth::id())->findOrFail($messageId);
        $message->is_flagged = true;
        $message->flagged_reason = $reason;
        $message->save();

        if ($this->inspectedMessage && $this->inspectedMessage->id === $messageId) {
            $this->inspectedMessage->is_flagged = true;
            $this->inspectedMessage->flagged_reason = $reason;
        }

        session()->flash('success', 'Inquiry flagged and forwarded to Trust & Safety moderation.');
    }

    public function blockSenderFromMessage(int $messageId): void
    {
        $message = ContactMessage::where('recipient_id', Auth::id())->findOrFail($messageId);

        if ($message->sender_id && $message->sender_id !== Auth::id()) {
            UserBlock::firstOrCreate([
                'blocker_id' => Auth::id(),
                'blocked_id' => $message->sender_id,
            ], [
                'reason' => 'Blocked via messages inbox',
                'created_at' => now(),
            ]);
            session()->flash('success', 'Sender has been blocked. They will not be able to message you again.');
        } else {
            session()->flash('success', 'Inquiry flagged as spam.');
        }

        $this->flagMessage($messageId, 'spam');
        $this->closeMessageModal();
    }

    public function deleteMessage(int $messageId): void
    {
        $message = ContactMessage::where(function ($q) {
            $q->where('recipient_id', Auth::id())
              ->orWhere('sender_id', Auth::id());
        })->findOrFail($messageId);
        
        $message->delete();

        if ($this->inspectedMessage && $this->inspectedMessage->id === $messageId) {
            $this->closeMessageModal();
        }

        session()->flash('success', 'Inquiry deleted from inbox.');
    }

    public function toggleSelectAllMessages(): void
    {
        if ($this->selectAllMessages) {
            $this->selectedMessages = [];
            $this->selectAllMessages = false;
        } else {
            $query = ContactMessage::where('recipient_id', Auth::id());
            if ($this->messageFilter === 'unread') {
                $query->where('is_read', false);
            } elseif ($this->messageFilter === 'replied') {
                $query->where('is_replied', true);
            }
            $this->selectedMessages = $query->pluck('id')->map(fn($id) => (int)$id)->toArray();
            $this->selectAllMessages = true;
        }
    }

    public function bulkMarkRead(): void
    {
        if (!empty($this->selectedMessages)) {
            ContactMessage::where('recipient_id', Auth::id())
                ->whereIn('id', $this->selectedMessages)
                ->update(['is_read' => true, 'read_at' => now()]);
            $this->selectedMessages = [];
            $this->selectAllMessages = false;
            session()->flash('success', 'Selected messages marked as read.');
        }
    }

    public function bulkMarkUnread(): void
    {
        if (!empty($this->selectedMessages)) {
            ContactMessage::where('recipient_id', Auth::id())
                ->whereIn('id', $this->selectedMessages)
                ->update(['is_read' => false, 'read_at' => null]);
            $this->selectedMessages = [];
            $this->selectAllMessages = false;
            session()->flash('success', 'Selected messages marked as unread.');
        }
    }

    public function bulkDeleteMessages(): void
    {
        if (!empty($this->selectedMessages)) {
            ContactMessage::where(function ($q) {
                $q->where('recipient_id', Auth::id())
                  ->orWhere('sender_id', Auth::id());
            })->whereIn('id', $this->selectedMessages)->delete();
            
            $this->selectedMessages = [];
            $this->selectAllMessages = false;
            session()->flash('success', 'Selected messages deleted.');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Promotions & Featured Listings
    |--------------------------------------------------------------------------
    */

    public function openPromoteModal(?int $listingId = null): void
    {
        $this->promoteListingId = $listingId;
        $plan = PricingPlan::where('plan_type', 'featured_spot')->first();
        $this->selectedPlanId = $plan?->id;
        $this->selectedPlacement = 'category_top';
        $this->showPromoteModal = true;
    }

    public function closePromoteModal(): void
    {
        $this->showPromoteModal = false;
        $this->promoteListingId = null;
    }

    public function activatePromotion(): void
    {
        $this->validate([
            'promoteListingId' => ['required', 'exists:listings,id'],
            'selectedPlanId' => ['required', 'exists:pricing_plans,id'],
            'selectedPlacement' => ['required', 'in:homepage,city_top,category_top'],
        ]);

        $listing = Listing::where('user_id', Auth::id())->findOrFail($this->promoteListingId);
        $plan = PricingPlan::findOrFail($this->selectedPlanId);

        $promotionService = app(PromotionService::class);
        $result = $promotionService->promoteListing(
            listing: $listing,
            plan: $plan,
            paymentData: [
                'gateway' => config('monetization.default', 'sandbox'),
                'payment_token' => 'tok_sandbox_default',
                'card_last_four' => '4242',
                'card_brand' => 'Visa',
                'ip_address' => request()->ip() ?: '127.0.0.1',
            ],
            user: Auth::user(),
            placement: $this->selectedPlacement
        );

        $durationDays = $plan->duration_days ?: 7;
        $this->closePromoteModal();
        session()->flash('success', "Listing [{$listing->title}] has been successfully promoted as Featured for {$durationDays} days!");
    }

    /*
    |--------------------------------------------------------------------------
    | Subscriptions & Memberships
    |--------------------------------------------------------------------------
    */

    public function cancelSubscription(int $subscriptionId): void
    {
        $sub = Subscription::where('user_id', Auth::id())->findOrFail($subscriptionId);
        app(SubscriptionService::class)->cancelSubscription($sub);

        session()->flash('success', 'Your subscription has been canceled and will not renew.');
    }

    public function subscribeToPlan(int $planId): void
    {
        $plan = PricingPlan::findOrFail($planId);

        app(SubscriptionService::class)->createSubscription(
            user: Auth::user(),
            plan: $plan,
            paymentData: [
                'gateway' => config('monetization.default', 'sandbox'),
                'payment_token' => 'tok_sandbox_default',
                'card_last_four' => '4242',
                'card_brand' => 'Visa',
                'ip_address' => request()->ip() ?: '127.0.0.1',
            ]
        );

        session()->flash('success', "You have successfully subscribed to {$plan->name}!");
    }

    /*
    |--------------------------------------------------------------------------
    | Profile Settings
    |--------------------------------------------------------------------------
    */

    public function saveProfile(): void
    {
        $this->validate([
            'displayName' => ['required', 'string', 'max:60'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'gender' => ['nullable', 'string', 'max:50'],
            'seeking' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'stateCode' => ['nullable', 'string', 'max:5'],
            'zipCode' => ['nullable', 'string', 'max:10'],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ]);

        /** @var User $user */
        $user = Auth::user();
        $user->name = $this->displayName;
        $user->save();

        $profile = $user->profile ?? new UserProfile(['user_id' => $user->id]);
        $profile->display_name = $this->displayName;
        $profile->bio = $this->bio;
        $profile->gender = $this->gender;
        $profile->seeking = $this->seeking;
        $profile->city = $this->city;
        $profile->state_code = $this->stateCode;
        $profile->zip_code = $this->zipCode;

        if ($this->avatar) {
            $path = $this->avatar->store('avatars', 'public');
            $profile->avatar_path = $path;
            $this->avatar = null;
        }

        $profile->save();
        session()->flash('success', 'Profile settings updated successfully.');
    }

    public function removeAvatar(): void
    {
        /** @var User $user */
        $user = Auth::user();
        if ($user->profile && $user->profile->avatar_path) {
            $user->profile->avatar_path = null;
            $user->profile->save();
        }

        session()->flash('success', 'Profile photo removed.');
    }

    /*
    |--------------------------------------------------------------------------
    | Privacy Settings
    |--------------------------------------------------------------------------
    */

    public function savePrivacySettings(): void
    {
        /** @var User $user */
        $user = Auth::user();

        $profile = $user->profile ?? new UserProfile(['user_id' => $user->id]);
        $profile->hide_location_details = $this->hideLocationDetails;
        $profile->hide_age = $this->hideAge;
        $profile->save();

        $pref = $user->preferences ?? new UserPreference(['user_id' => $user->id]);
        $pref->allow_contact_messages = $this->allowContactMessages;
        $pref->require_verified_senders = $this->requireVerifiedSenders;
        $pref->save();

        session()->flash('success', 'Privacy preferences saved.');
    }

    /*
    |--------------------------------------------------------------------------
    | Age Assurance
    |--------------------------------------------------------------------------
    */

    public function submitAgeAssurance(): void
    {
        $this->validate([
            'declaredBirthdate' => ['required', 'date', 'before:-18 years'],
            'ageAffirmation' => ['accepted'],
        ], [
            'declaredBirthdate.before' => 'You must be at least 18 years of age to access LocalDateHub.',
            'ageAffirmation.accepted' => 'You must affirm under penalty of perjury that you are of legal adult age.',
        ]);

        /** @var User $user */
        $user = Auth::user();
        $user->is_age_verified = true;
        $user->save();

        AgeAssurance::create([
            'user_id' => $user->id,
            'method' => 'self_attestation_audit',
            'status' => 'verified',
            'declared_birthdate' => $this->declaredBirthdate,
            'document_hash' => hash('sha256', "audit-age-{$user->id}-" . now()),
            'verified_at' => now(),
            'verification_metadata' => [
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'affirmed' => true,
            ],
        ]);

        session()->flash('success', 'Age verification confirmed! You now carry the 18+ Age Verified badge.');
    }

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    */

    public function updatePassword(): void
    {
        $this->validate([
            'currentPassword' => ['required'],
            'newPassword' => ['required', 'string', 'min:8', 'same:newPasswordConfirmation'],
        ]);

        /** @var User $user */
        $user = Auth::user();

        if (!Hash::check($this->currentPassword, $user->password)) {
            throw ValidationException::withMessages([
                'currentPassword' => 'The provided current password does not match our records.',
            ]);
        }

        $user->password = Hash::make($this->newPassword);
        $user->save();

        $this->currentPassword = '';
        $this->newPassword = '';
        $this->newPasswordConfirmation = '';

        session()->flash('success', 'Password successfully updated.');
    }

    /*
    |--------------------------------------------------------------------------
    | Blocked Users Management
    |--------------------------------------------------------------------------
    */

    public function blockUser(): void
    {
        $this->validate([
            'blockUsername' => ['required', 'string'],
            'blockReason' => ['nullable', 'string', 'max:150'],
        ]);

        $targetUser = User::where('name', $this->blockUsername)
            ->orWhere('email', $this->blockUsername)
            ->first();

        if (!$targetUser) {
            throw ValidationException::withMessages([
                'blockUsername' => "No user found matching '{$this->blockUsername}'.",
            ]);
        }

        if ($targetUser->id === Auth::id()) {
            throw ValidationException::withMessages([
                'blockUsername' => 'You cannot block your own account.',
            ]);
        }

        UserBlock::firstOrCreate([
            'blocker_id' => Auth::id(),
            'blocked_id' => $targetUser->id,
        ], [
            'reason' => $this->blockReason ?: 'Blocked by user',
            'created_at' => now(),
        ]);

        $this->blockUsername = '';
        $this->blockReason = '';
        session()->flash('success', "User [{$targetUser->name}] has been blocked.");
    }

    public function unblockUser(int $blockId): void
    {
        UserBlock::where('blocker_id', Auth::id())->where('id', $blockId)->delete();
        session()->flash('success', 'User unblocked.');
    }

    /*
    |--------------------------------------------------------------------------
    | Account Deletion
    |--------------------------------------------------------------------------
    */

    public function confirmAccountDeletion(): void
    {
        $this->showDeleteAccountModal = true;
    }

    public function deleteAccount(): void
    {
        $this->validate([
            'deleteAccountPassword' => ['required'],
        ]);

        /** @var User $user */
        $user = Auth::user();

        // Compliance evidence preservation check
        $canErase = app(\App\Modules\Moderation\Services\ComplianceService::class)->canEraseUserData($user);
        if (!$canErase['can_erase']) {
            throw ValidationException::withMessages([
                'deleteAccountPassword' => "Account erasure cannot be processed at this time: {$canErase['reason']}",
            ]);
        }

        if (!Hash::check($this->deleteAccountPassword, $user->password)) {
            throw ValidationException::withMessages([
                'deleteAccountPassword' => 'Incorrect password provided.',
            ]);
        }

        // Soft delete all user listings
        $user->listings()->delete();

        // Mark account deleted
        $user->account_status = 'deleted';
        $user->save();
        $user->delete();

        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();

        $this->redirectRoute('home');
    }

    /*
    |--------------------------------------------------------------------------
    | Query Helpers
    |--------------------------------------------------------------------------
    */

    protected function getMyListingsQuery()
    {
        $query = Listing::query()
            ->where('user_id', Auth::id())
            ->with(['category', 'city.state', 'primaryPhoto']);

        if (!empty($this->listingSearch)) {
            $query->where('title', 'like', '%' . $this->listingSearch . '%');
        }

        if ($this->listingStatus !== 'all') {
            $query->where('status', $this->listingStatus);
        }

        if ($this->listingCategory) {
            $query->where('category_id', $this->listingCategory);
        }

        return match ($this->listingSort) {
            'views' => $query->orderByDesc('view_count'),
            'expires' => $query->orderBy('expires_at'),
            default => $query->latest(),
        };
    }

    public function render(): View
    {
        $userId = Auth::id();

        // Overview Metric Counts
        $totalListings = Listing::where('user_id', $userId)->count();
        $activeListingsCount = Listing::where('user_id', $userId)->where('status', Listing::STATUS_ACTIVE)->count();
        $pendingListingsCount = Listing::where('user_id', $userId)->where('status', Listing::STATUS_PENDING_REVIEW)->count();
        $rejectedListingsCount = Listing::where('user_id', $userId)->where('status', Listing::STATUS_REJECTED)->count();
        $expiredListingsCount = Listing::where('user_id', $userId)->where('status', Listing::STATUS_EXPIRED)->count();
        $suspendedListingsCount = Listing::where('user_id', $userId)->where('status', Listing::STATUS_SUSPENDED)->count();
        $savedListingsCount = Auth::user()->favorites()->count();

        $totalViews = Listing::where('user_id', $userId)->sum('view_count');
        $totalReplies = Listing::where('user_id', $userId)->sum('reply_count');
        $totalFavorites = Listing::where('user_id', $userId)->sum('favorite_count');

        $unreadInquiriesCount = ContactMessage::where('recipient_id', $userId)
            ->where('is_read', false)
            ->count();

        // Active Subscription
        $activeSubscription = Subscription::where('user_id', $userId)
            ->where('status', 'active')
            ->with('plan')
            ->latest()
            ->first();

        // Overview Recent Listings
        $recentListings = Listing::where('user_id', $userId)
            ->with(['category', 'city.state'])
            ->latest()
            ->take(5)
            ->get();

        // Overview Recent Inquiries
        $recentInquiries = ContactMessage::where('recipient_id', $userId)
            ->with(['listing'])
            ->latest()
            ->take(4)
            ->get();

        // Paginated My Listings for Listings Tab
        $myListings = $this->getMyListingsQuery()->paginate(10);

        // Saved Listings Query
        $savedQuery = Auth::user()->favorites()
            ->with(['category', 'city.state', 'primaryPhoto']);

        if (!empty($this->savedSearch)) {
            $savedQuery->where('title', 'like', '%' . $this->savedSearch . '%');
        }
        $savedListings = $savedQuery->paginate(8, ['*'], 'savedPage');

        // Contact Messages Query
        if ($this->messageFolder === 'sent') {
            $messagesQuery = ContactMessage::where('sender_id', $userId)
                ->with(['listing.city.state', 'recipient'])
                ->latest();
        } else {
            $messagesQuery = ContactMessage::where('recipient_id', $userId)
                ->with(['listing.city.state', 'sender'])
                ->latest();

            if ($this->messageFilter === 'unread') {
                $messagesQuery->where('is_read', false);
            } elseif ($this->messageFilter === 'replied') {
                $messagesQuery->where('is_replied', true);
            } elseif ($this->messageFilter === 'flagged') {
                $messagesQuery->where('is_flagged', true);
            }
        }

        if (!empty($this->messageListingFilter)) {
            $messagesQuery->where('listing_id', $this->messageListingFilter);
        }

        if (!empty($this->messageSearch)) {
            $messagesQuery->where(function ($q) {
                $q->where('sender_name', 'like', '%' . $this->messageSearch . '%')
                  ->orWhere('subject', 'like', '%' . $this->messageSearch . '%');
            });
        }
        $messages = $messagesQuery->paginate(10, ['*'], 'messagesPage');

        $totalInboxCount = ContactMessage::where('recipient_id', $userId)->count();
        $totalSentCount = ContactMessage::where('sender_id', $userId)->count();
        $totalUnreadCount = ContactMessage::where('recipient_id', $userId)->where('is_read', false)->count();
        $totalRepliedCount = ContactMessage::where('recipient_id', $userId)->where('is_replied', true)->count();
        $totalFlaggedCount = ContactMessage::where('recipient_id', $userId)->where('is_flagged', true)->count();
        $userListingsForMessages = Listing::where('user_id', $userId)->orderBy('title')->get(['id', 'title']);

        // Analytics Data
        $analyticsListings = Listing::where('user_id', $userId)
            ->with(['category', 'city.state'])
            ->orderByDesc('view_count')
            ->get();

        // Promotions & Pricing Plans
        $activePromotions = FeaturedListing::where('user_id', $userId)
            ->with(['listing', 'plan'])
            ->latest()
            ->get();

        $pricingPlans = PricingPlan::where('is_active', true)->get();
        $userActiveListings = Listing::where('user_id', $userId)
            ->where('status', Listing::STATUS_ACTIVE)
            ->get(['id', 'title']);

        // Billing History
        $billingTransactions = PaymentTransaction::where('user_id', $userId)
            ->with(['plan', 'listing', 'invoice'])
            ->latest()
            ->paginate(10, ['*'], 'billingPage');

        // Blocked Users
        $blockedUsers = UserBlock::where('blocker_id', $userId)
            ->with('blocked')
            ->latest('created_at')
            ->get();

        $categories = Category::where('is_active', true)->orderBy('name')->get();

        return view('livewire.account-dashboard', [
            'totalListings' => $totalListings,
            'activeListingsCount' => $activeListingsCount,
            'pendingListingsCount' => $pendingListingsCount,
            'rejectedListingsCount' => $rejectedListingsCount,
            'expiredListingsCount' => $expiredListingsCount,
            'suspendedListingsCount' => $suspendedListingsCount,
            'savedListingsCount' => $savedListingsCount,
            'totalViews' => $totalViews,
            'totalReplies' => $totalReplies,
            'totalFavorites' => $totalFavorites,
            'unreadInquiriesCount' => $unreadInquiriesCount,
            'activeSubscription' => $activeSubscription,
            'recentListings' => $recentListings,
            'recentInquiries' => $recentInquiries,
            'myListings' => $myListings,
            'savedListings' => $savedListings,
            'messages' => $messages,
            'totalInboxCount' => $totalInboxCount,
            'totalSentCount' => $totalSentCount,
            'totalUnreadCount' => $totalUnreadCount,
            'totalRepliedCount' => $totalRepliedCount,
            'totalFlaggedCount' => $totalFlaggedCount,
            'userListingsForMessages' => $userListingsForMessages,
            'analyticsListings' => $analyticsListings,
            'activePromotions' => $activePromotions,
            'pricingPlans' => $pricingPlans,
            'userActiveListings' => $userActiveListings,
            'billingTransactions' => $billingTransactions,
            'blockedUsers' => $blockedUsers,
            'categories' => $categories,
        ]);
    }
}
