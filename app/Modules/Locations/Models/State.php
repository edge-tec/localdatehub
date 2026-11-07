<?php

namespace App\Modules\Locations\Models;

use App\Modules\Listings\Models\Listing;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class State extends Model
{
    use HasFactory;

    protected $fillable = [
        'country_id',
        'name',
        'code',
        'fips_code',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
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
}
