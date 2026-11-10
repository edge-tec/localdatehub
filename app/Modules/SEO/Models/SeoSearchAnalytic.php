<?php

namespace App\Modules\SEO\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SeoSearchAnalytic extends Model
{
    use HasFactory;

    protected $table = 'seo_search_analytics';

    protected $fillable = [
        'date',
        'query',
        'page_url',
        'country_code',
        'device',
        'clicks',
        'impressions',
        'ctr',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'clicks' => 'integer',
            'impressions' => 'integer',
            'ctr' => 'decimal:4',
            'position' => 'decimal:2',
        ];
    }
}

