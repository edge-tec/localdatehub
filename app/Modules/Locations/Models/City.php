<?php

namespace App\Modules\Locations\Models;

use App\Modules\Listings\Models\Listing;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class City extends Model
{
    use HasFactory;

    protected $fillable = [
        'state_id',
        'county_id',
        'name',
        'slug',
        'city_type',
        'latitude',
        'longitude',
        'population',
        'timezone',
        'is_popular',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'is_popular' => 'boolean',
            'is_active' => 'boolean',
            'population' => 'integer',
        ];
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function county(): BelongsTo
    {
        return $this->belongsTo(County::class);
    }

    public function zipCodes(): HasMany
    {
        return $this->hasMany(ZipCode::class);
    }

    public function aliases(): HasMany
    {
        return $this->hasMany(LocationAlias::class);
    }

    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class);
    }

    public const VALID_CITY_TYPES = [
        'city',
        'city and borough',
        'municipality',
        'consolidated government',
        'metropolitan government',
        'unified government',
        'urban county',
        'corporation',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereIn('city_type', self::VALID_CITY_TYPES)
                  ->orWhereNull('city_type');
            });
    }

    public function scopeOnlyCities(Builder $query): Builder
    {
        return $this->scopeActive($query);
    }

    public function scopePopular(Builder $query): Builder
    {
        return $this->scopeActive($query)->where('is_popular', true);
    }
}
