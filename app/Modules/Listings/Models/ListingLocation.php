<?php

namespace App\Modules\Listings\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ListingLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'listing_id',
        'neighborhood',
        'cross_streets',
        'postal_code',
        'latitude',
        'longitude',
        'fuzzed_latitude',
        'fuzzed_longitude',
        'hide_exact_distance',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'fuzzed_latitude' => 'float',
            'fuzzed_longitude' => 'float',
            'hide_exact_distance' => 'boolean',
        ];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }
}
