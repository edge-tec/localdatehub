<?php

namespace App\Modules\SEO\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SeoUrlRecord extends Model
{
    use HasFactory;

    protected $table = 'seo_url_records';

    protected $fillable = [
        'url',
        'url_type',
        'model_type',
        'model_id',
        'canonical_url',
        'indexing_status',
        'indexing_eligibility',
        'sitemap_status',
        'sitemap_name',
        'http_status',
        'meta_title',
        'meta_description',
        'h1',
        'last_crawled_at',
        'last_indexed_check_at',
        'last_submitted_at',
        'organic_clicks',
        'organic_impressions',
        'average_position',
        'average_ctr',
        'seo_health_score',
        'has_issues',
    ];

    protected function casts(): array
    {
        return [
            'last_crawled_at' => 'datetime',
            'last_indexed_check_at' => 'datetime',
            'last_submitted_at' => 'datetime',
            'http_status' => 'integer',
            'organic_clicks' => 'integer',
            'organic_impressions' => 'integer',
            'average_position' => 'decimal:2',
            'average_ctr' => 'decimal:2',
            'seo_health_score' => 'integer',
            'has_issues' => 'boolean',
        ];
    }

    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    public function issues(): HasMany
    {
        return $this->hasMany(SeoIssue::class, 'url_record_id');
    }

    public function queueItems(): HasMany
    {
        return $this->hasMany(SeoIndexingQueue::class, 'url_record_id');
    }
}

