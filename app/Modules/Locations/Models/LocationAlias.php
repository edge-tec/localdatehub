<?php

namespace App\Modules\Locations\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LocationAlias extends Model
{
    use HasFactory;

    protected $fillable = [
        'city_id',
        'alias',
        'alias_type',
    ];

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
}
