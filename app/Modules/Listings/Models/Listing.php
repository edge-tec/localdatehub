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

    protected $fillable = [
        'user_id',
        'category_id',
        'state_id',
        'city_id',
        'zip_code_id',
        'title',
        'slug',
        'description',
        'poster_age',
        'poster_gender',
        'intent',
        'status',
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
        return $this->hasMany(ListingRevision::class)->latest();
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(ListingStatusHistory::class)->latest();
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

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
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
