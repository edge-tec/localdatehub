<?php

namespace App\Models;

use App\Modules\Accounts\Models\AgeAssurance;
use App\Modules\Accounts\Models\UserBlock;
use App\Modules\Accounts\Models\UserConsent;
use App\Modules\Accounts\Models\UserPreference;
use App\Modules\Accounts\Models\UserProfile;
use App\Modules\Admin\Models\Role;
use App\Modules\Billing\Models\FeaturedListing;
use App\Modules\Billing\Models\PaymentTransaction;
use App\Modules\Billing\Models\Subscription;
use App\Modules\Contact\Models\ContactMessage;
use App\Modules\Listings\Models\Listing;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'username',
        'email',
        'phone',
        'password',
        'role',
        'account_status',
        'is_age_verified',
        'is_identity_verified',
        'two_factor_enabled',
        'two_factor_secret',
        'phone_verified_at',
        'email_verified_at',
        'google_id',
        'oauth_provider',
        'oauth_id',
        'avatar',
        'last_active_at',
        'last_login_ip',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'last_active_at' => 'datetime',
            'password' => 'hashed',
            'is_age_verified' => 'boolean',
            'is_identity_verified' => 'boolean',
            'two_factor_enabled' => 'boolean',
        ];
    }

    // Relationships
    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    public function preferences(): HasOne
    {
        return $this->hasOne(UserPreference::class);
    }

    public function ageAssurances(): HasMany
    {
        return $this->hasMany(AgeAssurance::class);
    }

    public function consents(): HasMany
    {
        return $this->hasMany(UserConsent::class);
    }

    public function suspensions(): HasMany
    {
        return $this->hasMany(\App\Modules\Moderation\Models\UserSuspension::class);
    }

    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class);
    }

    public function favorites(): BelongsToMany
    {
        return $this->belongsToMany(Listing::class, 'listing_favorites')->withPivot('created_at');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles')->withTimestamps();
    }

    public function sentMessages(): HasMany
    {
        return $this->hasMany(ContactMessage::class, 'sender_id');
    }

    public function receivedMessages(): HasMany
    {
        return $this->hasMany(ContactMessage::class, 'recipient_id');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    public function featuredListings(): HasMany
    {
        return $this->hasMany(FeaturedListing::class);
    }

    public function blockedUsers(): HasMany
    {
        return $this->hasMany(UserBlock::class, 'blocker_id');
    }

    public function blockedBy(): HasMany
    {
        return $this->hasMany(UserBlock::class, 'blocked_id');
    }

    public function cryptoPayments(): HasMany
    {
        return $this->hasMany(\App\Modules\Payments\Crypto\Models\CryptoPayment::class);
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(\App\Modules\Monetization\Models\UserWallet::class);
    }

    public function walletTransactions(): HasMany
    {
        return $this->hasMany(\App\Modules\Monetization\Models\WalletTransaction::class);
    }

    public function postingOverride(): HasOne
    {
        return $this->hasOne(\App\Modules\Monetization\Models\UserPostingOverride::class);
    }

    public function verifications(): HasMany
    {
        return $this->hasMany(\App\Modules\Verification\Models\IdentityVerification::class);
    }

    public function latestVerification(): HasOne
    {
        return $this->hasOne(\App\Modules\Verification\Models\IdentityVerification::class)->latestOfMany();
    }

    public function getWalletBalance(): int
    {
        return (int) ($this->wallet()->value('balance') ?? 0);
    }

    public function getAccountType(): string
    {
        if ($this->role === 'business') {
            return 'business';
        }
        if ($this->subscriptions()->where('status', 'active')->where('ends_at', '>', now())->exists()) {
            return 'premium';
        }
        if ($this->is_identity_verified) {
            return 'verified';
        }
        if ($this->created_at && $this->created_at->gt(now()->subDays(14))) {
            return 'new_account';
        }
        return 'regular';
    }

    public function listingBoosts(): HasMany
    {
        return $this->hasMany(\App\Modules\Payments\Crypto\Models\ListingBoost::class);
    }

    // Scopes & Helpers
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('account_status', 'active');
    }

    public function scopeAdultVerified(Builder $query): Builder
    {
        return $query->where('is_age_verified', true);
    }

    public function hasRole(string|array $roleSlug): bool
    {
        if ($this->role === 'super_admin' || $this->role === 'admin') {
            return true;
        }

        $roles = (array) $roleSlug;
        if (in_array($this->role, $roles, true)) {
            return true;
        }

        return $this->roles()->whereIn('slug', $roles)->exists();
    }

    public function hasPermission(string $permissionSlug): bool
    {
        if ($this->role === 'super_admin' || $this->role === 'admin') {
            return true;
        }

        // Permissions granted through the primary role column (e.g. role = 'finance_manager')
        $primaryRoleGrants = \App\Modules\Admin\Models\Role::where('slug', $this->role)
            ->whereHas('permissions', fn($q) => $q->where('slug', $permissionSlug))
            ->exists();

        if ($primaryRoleGrants) {
            return true;
        }

        // Permissions granted through additional pivot role assignments
        return $this->roles()
            ->whereHas('permissions', fn($q) => $q->where('slug', $permissionSlug))
            ->exists();
    }

    public function isStaff(): bool
    {
        $staffRoles = [
            'super_admin', 'admin', 'senior_moderator', 'listing_moderator',
            'compliance_officer', 'customer_support', 'finance_manager', 'seo_manager',
        ];

        return in_array($this->role, $staffRoles, true) || $this->roles()->whereIn('slug', $staffRoles)->exists();
    }

    public function isSuspended(): bool
    {
        return $this->account_status === 'suspended';
    }
}
