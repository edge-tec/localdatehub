<?php

namespace App\Modules\Listings\Models;

use App\Models\User;
use App\Modules\Categories\Models\Category;
use App\Modules\Contact\Models\ContactMessage;
use App\Modules\Locations\Models\City;
use App\Modules\Locations\Models\State;
use App\Modules\Locations\Models\ZipCode;
use Illuminate\Database\Eloquent\Builder;
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
        'status',
        'rejection_reason',
        'rejection_count',
        'is_autosaved',
        'is_featured',
        'is_verified_poster',
        'has_photos',
        'view_count',
        'reply_count',
        'favorite_count',
        'published_at',
        'expires_at',
        'renewed_at',
    ];

    protected function casts(): array
    {
        return [
            'poster_age' => 'integer',
            'rejection_count' => 'integer',
            'is_autosaved' => 'boolean',
            'is_featured' => 'boolean',
            'is_verified_poster' => 'boolean',
            'has_photos' => 'boolean',
            'view_count' => 'integer',
            'reply_count' => 'integer',
            'favorite_count' => 'integer',
            'published_at' => 'datetime',
            'expires_at' => 'datetime',
            'renewed_at' => 'datetime',
        ];
    }

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
        return $this->hasMany(ListingMedia::class)->orderBy('display_order');
    }

    public function approvedMedia(): HasMany
    {
        return $this->hasMany(ListingMedia::class)
            ->where('moderation_status', 'approved')
            ->orderBy('display_order');
    }

    public function primaryPhoto(): HasOne
    {
        return $this->hasOne(ListingMedia::class)->where('is_primary', true);
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
        return $this->belongsToMany(User::class, 'listing_favorites')->withTimestamps();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ContactMessage::class);
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
}
