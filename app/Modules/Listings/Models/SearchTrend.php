<?php

namespace App\Modules\Listings\Models;

use App\Modules\Categories\Models\Category;
use App\Modules\Locations\Models\City;
use App\Modules\Locations\Models\State;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SearchTrend extends Model
{
    use HasFactory;

    protected $fillable = [
        'query',
        'category_id',
        'state_id',
        'city_id',
        'results_count',
        'hits',
        'last_searched_at',
    ];

    protected function casts(): array
    {
        return [
            'results_count' => 'integer',
            'hits' => 'integer',
            'last_searched_at' => 'datetime',
        ];
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
}
