<?php

namespace App\Modules\Locations\Models;

use App\Modules\Categories\Models\Category;
use App\Modules\Listings\Models\Listing;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Country extends Model
{
    use HasFactory;

    public const FIVE_MARKETPLACES = ['GB', 'CA', 'AU', 'IE', 'NZ'];
    public const FIVE_MARKETPLACE_SLUGS = ['gb', 'ca', 'au', 'ie', 'nz'];

    protected $fillable = [
        'code',
        'slug',
        'name',
        'iso3',
        'phone_code',
        'currency_code',
        'currency_symbol',
        'flag',
        'timezone',
        'division_label',
        'seo_title',
        'seo_description',
        'is_active',
        'is_marketplace_enabled',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_marketplace_enabled' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Country $country) {
            if (empty($country->slug) && !empty($country->code)) {
                $country->slug = strtolower($country->code);
            }
        });
    }

    public function iso2(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->code,
            set: fn (string $value) => ['code' => strtoupper($value)],
        );
    }

    public function status(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->is_active ? 'active' : 'inactive',
        );
    }

    public function states(): HasMany
    {
        return $this->hasMany(State::class);
    }

    public function administrativeAreas(): HasMany
    {
        return $this->hasMany(AdministrativeArea::class, 'country_id');
    }

    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }

    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'country_categories')
            ->withPivot(['is_active', 'sort_order'])
            ->withTimestamps();
    }

    public function scopeMarketplaceEnabled(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where('is_marketplace_enabled', true)
            ->whereIn('code', self::FIVE_MARKETPLACES);
    }

    public function formatPrice(float|int|string|null $amount): string
    {
        $symbol = $this->currency_symbol ?: '£';
        if ($amount === null || $amount === '' || (float) $amount <= 0) {
            return 'Free';
        }

        return $symbol . number_format((float) $amount, 2);
    }
}
