<?php

namespace App\Modules\SEO\Models;

use App\Modules\Categories\Models\Category;
use App\Modules\Listings\Models\Listing;
use App\Modules\Locations\Models\City;
use App\Modules\Locations\Models\State;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeoKeyword extends Model
{
    use HasFactory;

    protected $table = 'seo_keywords';

    protected $fillable = [
        'keyword',
        'group_id',
        'search_volume',
        'keyword_difficulty',
        'search_intent',
        'cpc',
        'target_url',
        'related_keywords',
        'category_id',
        'state_id',
        'city_id',
        'listing_id',
        'priority',
        'tracking_status',
        'ranking_position',
        'data_source',
        'last_retrieved_at',
    ];

    protected function casts(): array
    {
        return [
            'search_volume' => 'integer',
            'keyword_difficulty' => 'integer',
            'cpc' => 'decimal:2',
            'related_keywords' => 'array',
            'ranking_position' => 'integer',
            'last_retrieved_at' => 'datetime',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(SeoKeywordGroup::class, 'group_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class, 'state_id');
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class, 'listing_id');
    }
}

