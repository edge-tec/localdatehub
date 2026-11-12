<?php

namespace App\Modules\Listings\Models;

use App\Models\User;
use App\Modules\Categories\Models\Category;
use App\Modules\Contact\Models\ContactMessage;
use App\Modules\Locations\Models\AdministrativeArea;
use App\Modules\Locations\Models\City;
use App\Modules\Locations\Models\Country;
use App\Modules\Locations\Models\State;
use App\Modules\Locations\Models\ZipCode;
use App\Modules\Verification\Models\IdentityVerification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Listing extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING_REVIEW = 'pending_review';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_PAUSED = 'paused';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'user_id',
        'category_id',
        'country_id',
        'state_id',
        'city_id',
        'zip_code_id',
        'title',
        'slug',
        'description',
        'content_hash',
        'poster_age',
        'poster_gender',
        'intent',
        'price',
        'currency_code',
        'status',
        'rejection_reason',
        'rejection_count',
        'is_autosaved',
        'is_featured',
        'promoted_until',
        'has_photos',
        'view_count',
        'reply_count',
        'favorite_count',
        'published_at',
        'expires_at',
        'renewed_at',
        'bumped_at',
    ];

    protected $hidden = [
        'ownerIdentityVerification',
    ];

    protected function casts(): array
    {
        return [
            'poster_age' => 'integer',
            'rejection_count' => 'integer',
            'is_autosaved' => 'boolean',
            'is_featured' => 'boolean',
            'promoted_until' => 'datetime',
            'is_verified_poster' => 'boolean',
            'has_photos' => 'boolean',
            'view_count' => 'integer',
            'reply_count' => 'integer',
            'favorite_count' => 'integer',
            'published_at' => 'datetime',
            'expires_at' => 'datetime',
            'renewed_at' => 'datetime',
            'bumped_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Listing $listing) {
            if (empty($listing->country_id) && $listing->state_id) {
                $state = State::with('country')->find($listing->state_id);
                if ($state) {
                    $listing->country_id = $state->country_id;
                    if (empty($listing->currency_code) && $state->country?->currency_code) {
                        $listing->currency_code = $state->country->currency_code;
                    }
                }
            }
            if (empty($listing->currency_code) && $listing->country_id) {
                $curr = Country::whereKey($listing->country_id)->value('currency_code');
                if ($curr) {
                    $listing->currency_code = $curr;
                }
            }
        });
    }

    // Relationships
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function administrativeArea(): BelongsTo
    {
        return $this->belongsTo(AdministrativeArea::class, 'state_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ownerIdentityVerification(): HasOne
    {
        return $this->hasOne(IdentityVerification::class, 'user_id', 'user_id')
            ->validApproved()
            ->latestOfMany();
    }

    /**
     * Authoritative identity verification check for listing owner.
     */
    public function hasVerifiedPoster(): bool
    {
        if (!$this->user_id) {
            return false;
        }

        if ($this->relationLoaded('ownerIdentityVerification')) {
            return $this->getRelation('ownerIdentityVerification')?->isVerified() ?? false;
        }

        if ($this->relationLoaded('user') && $this->user) {
            return $this->user->hasVerifiedIdentity();
        }

        return User::isUserIdentityVerified((int) $this->user_id);
    }

    /**
     * Strictly derive is_verified_poster from validated ID verification rather than stale flags.
     */
    protected function isVerifiedPoster(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->hasVerifiedPoster(),
        );
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function zipCode(): BelongsTo
    {
        return $this->belongsTo(ZipCode::class);
    }

    public function location(): HasOne
    {
        return $this->hasOne(ListingLocation::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(ListingMedia::class)
            ->where('moderation_status', '!=', 'rejected')
            ->orderBy('display_order');
    }

    public function photos(): HasMany
    {
        return $this->media();
    }

    public function allMedia(): HasMany
    {
        return $this->hasMany(ListingMedia::class)
            ->orderBy('display_order');
    }

    public function approvedMedia(): HasMany
    {
        return $this->hasMany(ListingMedia::class)
            ->where('moderation_status', '!=', 'rejected')
            ->orderBy('display_order');
    }

    public function primaryPhoto(): HasOne
    {
        return $this->hasOne(ListingMedia::class)
            ->where('moderation_status', '!=', 'rejected')
            ->where('is_primary', true);
    }

    public function getPrimaryPhotoUrlAttribute(): string
    {
        if ($this->primaryPhoto && $this->primaryPhoto->url) {
            return $this->primaryPhoto->url;
        }

        $fallback = $this->allMedia->where('moderation_status', '!=', 'rejected')->first();
        if ($fallback && $fallback->url) {
            return $fallback->url;
        }

        $index = (($this->id - 1) % 4) + 1;
        return asset("images/featured-{$index}.jpg");
    }

    public function getPrimaryThumbnailUrlAttribute(): string
    {
        if ($this->primaryPhoto && $this->primaryPhoto->thumbnail_url) {
            return $this->primaryPhoto->thumbnail_url;
        }

        $fallback = $this->allMedia->where('moderation_status', '!=', 'rejected')->first();
        if ($fallback && $fallback->thumbnail_url) {
            return $fallback->thumbnail_url;
        }

        $index = (($this->id - 1) % 4) + 1;
        return asset("images/featured-{$index}.jpg");
    }

    public function contact(): HasOne
    {
        return $this->hasOne(ListingContact::class);
    }

    public function attributes(): HasMany
    {
        return $this->hasMany(ListingAttribute::class);
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(ListingRevision::class)->latest('created_at');
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(ListingStatusHistory::class)->latest('created_at');
    }

    public function analytics(): HasMany
    {
        return $this->hasMany(ListingAnalytics::class);
    }

    public function favoritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'listing_favorites')->withPivot('created_at');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ContactMessage::class);
    }

    public function boosts(): HasMany
    {
        return $this->hasMany(\App\Modules\Payments\Crypto\Models\ListingBoost::class);
    }

    public function activeBoost(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(\App\Modules\Payments\Crypto\Models\ListingBoost::class)
            ->where('status', 'active')
            ->where('expires_at', '>', now());
    }

    public function cryptoPayments(): HasMany
    {
        return $this->hasMany(\App\Modules\Payments\Crypto\Models\CryptoPayment::class);
    }

    // Status Helpers
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE &&
            ($this->expires_at === null || $this->expires_at->isFuture());
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING_REVIEW;
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED ||
            ($this->status === self::STATUS_ACTIVE && $this->expires_at && $this->expires_at->isPast());
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function canBeRenewed(): bool
    {
        return in_array($this->status, [self::STATUS_ACTIVE, self::STATUS_EXPIRED]);
    }

    /**
     * Compute standardized content hash for duplicate detection.
     */
    public function computeContentHash(): string
    {
        $normalized = strtolower(preg_replace('/\s+/', ' ', trim($this->title . ' ' . $this->description)));
        return hash('sha256', $normalized);
    }

    /**
     * Record a snapshot revision before major updates.
     */
    public function recordRevision(string $summary, ?int $userId = null): ListingRevision
    {
        $snapshot = $this->attributes()->with('selectedOption')->get()->map(fn ($attr) => [
            'code' => $attr->attribute_code,
            'text' => $attr->value_text,
            'number' => $attr->value_number,
            'option' => $attr->selectedOption?->label,
        ])->all();

        return ListingRevision::create([
            'listing_id' => $this->id,
            'user_id' => $userId ?: $this->user_id,
            'title' => $this->title,
            'description' => $this->description,
            'attributes_snapshot' => $snapshot,
            'change_summary' => $summary,
            'created_at' => now(),
        ]);
    }

    /**
     * Record status change in status history.
     */
    public function recordStatusChange(string $newStatus, ?string $reason = null, ?int $changedByUserId = null): ListingStatusHistory
    {
        $oldStatus = $this->status;
        $this->status = $newStatus;
        if ($reason) {
            $this->rejection_reason = $reason;
        }
        $this->save();

        return ListingStatusHistory::create([
            'listing_id' => $this->id,
            'from_status' => $oldStatus,
            'to_status' => $newStatus,
            'reason' => $reason,
            'changed_by_user_id' => $changedByUserId,
            'created_at' => now(),
        ]);
    }

    /**
     * Increment analytics metric for today.
     */
    public function recordAnalyticsEvent(string $metric = 'views', int $count = 1): void
    {
        $today = now()->toDateString();
        $record = ListingAnalytics::firstOrCreate(
            ['listing_id' => $this->id, 'metric_date' => $today],
            ['views' => 0, 'unique_visitors' => 0, 'contact_clicks' => 0, 'favorite_adds' => 0]
        );

        if (in_array($metric, ['views', 'unique_visitors', 'contact_clicks', 'favorite_adds'], true)) {
            $record->increment($metric, $count);
            if ($metric === 'views') {
                $this->increment('view_count', $count);
            }
        }
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
    }

    public function scopePendingReview(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING_REVIEW);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeInCity(Builder $query, int $cityId): Builder
    {
        return $query->where('city_id', $cityId);
    }

    public function scopeInState(Builder $query, int $stateId): Builder
    {
        return $query->where('state_id', $stateId);
    }

    public function scopeInCategory(Builder $query, int $categoryId): Builder
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeInCountry(Builder $query, Country|int|string $country): Builder
    {
        if ($country instanceof Country) {
            return $query->where('country_id', $country->id);
        }
        if (is_numeric($country)) {
            return $query->where('country_id', (int) $country);
        }

        $code = strtoupper(trim($country));
        return $query->whereHas('country', fn ($q) => $q->where('code', $code)->orWhere('slug', strtolower($code)));
    }

    public function formattedPrice(): string
    {
        $symbols = [
            'GBP' => '£',
            'CAD' => 'C$',
            'AUD' => 'A$',
            'EUR' => '€',
            'NZD' => 'NZ$',
            'USD' => '$',
        ];
        $symbol = $symbols[strtoupper((string) $this->currency_code)] ?? ($this->country?->currency_symbol ?: '$');
        if ($this->price === null || (float) $this->price <= 0) {
            return 'Free';
        }

        return $symbol . number_format((float) $this->price, 2);
    }
}
