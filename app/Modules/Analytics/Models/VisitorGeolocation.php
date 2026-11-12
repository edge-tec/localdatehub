<?php

namespace App\Modules\Analytics\Models;

use Illuminate\Database\Eloquent\Model;

class VisitorGeolocation extends Model
{
    protected $table = 'visitor_geolocation';

    protected $fillable = [
        'ip_address',
        'country_code',
        'country_name',
        'region_name',
        'city_name',
        'latitude',
        'longitude',
        'isp',
        'organization',
        'source',
        'cached_until',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'cached_until' => 'datetime',
        ];
    }
}

