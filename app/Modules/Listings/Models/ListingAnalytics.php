<?php

namespace App\Modules\Listings\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ListingAnalytics extends Model
{
    use HasFactory;

    protected $fillable = [
        'listing_id',
        'metric_date',
        'views',
        'unique_visitors',
        'contact_clicks',
        'favorite_adds',
    ];

    protected function casts(): array
    {
        return [
            'metric_date' => 'date',
            'views' => 'integer',
            'unique_visitors' => 'integer',
            'contact_clicks' => 'integer',
            'favorite_adds' => 'integer',
        ];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }
}
