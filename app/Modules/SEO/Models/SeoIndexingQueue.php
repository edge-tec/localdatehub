<?php

namespace App\Modules\SEO\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SeoIndexingQueue extends Model
{
    use HasFactory;

    protected $table = 'seo_indexing_queue';

    protected $fillable = [
        'url',
        'url_record_id',
        'status',
        'action',
        'api_type',
        'priority',
        'attempts',
        'max_attempts',
        'last_error',
        'next_attempt_at',
        'submitted_at',
        'response_payload',
    ];

    protected function casts(): array
    {
        return [
            'priority' => 'integer',
            'attempts' => 'integer',
            'max_attempts' => 'integer',
            'next_attempt_at' => 'datetime',
            'submitted_at' => 'datetime',
            'response_payload' => 'array',
        ];
    }

    public function urlRecord(): BelongsTo
    {
        return $this->belongsTo(SeoUrlRecord::class, 'url_record_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(SeoIndexingLog::class, 'queue_id');
    }
}

