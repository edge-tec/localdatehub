<?php

namespace App\Modules\Locations\Models;

use App\Modules\Listings\Models\Listing;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class State extends Model
{
    use HasFactory;

    protected $fillable = [
        'country_id',
        'name',
        'slug',
        'code',
        'fips_code',
        'is_territory',
        'is_active',
    ];

    protected $appends = [
        'abbreviation',
        'country_code',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_territory' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (State $state) {
            if (empty($state->slug)) {
                $state->slug = Str::slug($state->name);
            }
        });
    }

    public function abbreviation(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->code,
        );
    }

    public function countryCode(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->country?->code ?? 'US',
        );
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }

    public function counties(): HasMany
    {
        return $this->hasMany(County::class);
    }

    public function zipCodes(): HasMany
    {
        return $this->hasMany(ZipCode::class);
    }

    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class);
    }

    public function scopeStatesOnly($query)
    {
        return $query->where('is_territory', false);
    }

    public function scopeTerritoriesOnly($query)
    {
        return $query->where('is_territory', true);
    }
}
