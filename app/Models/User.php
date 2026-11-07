<?php

namespace App\Models;

use App\Modules\Accounts\Models\AgeAssurance;
use App\Modules\Accounts\Models\UserConsent;
use App\Modules\Accounts\Models\UserPreference;
use App\Modules\Accounts\Models\UserProfile;
use App\Modules\Admin\Models\Role;
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

    // Scopes & Helpers
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('account_status', 'active');
    }

    public function scopeAdultVerified(Builder $query): Builder
    {
        return $query->where('is_age_verified', true);
    }

    public function hasRole(string $roleSlug): bool
    {
        if ($this->role === $roleSlug || $this->role === 'admin' || $this->role === 'super_admin') {
            return true;
        }

        return $this->roles->contains('slug', $roleSlug);
    }

    public function isSuspended(): bool
    {
        return $this->account_status === 'suspended';
    }
}
