<?php

namespace App\Modules\SEO\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeoIndexingLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'seo_indexing_logs';

    protected $fillable = [
        'queue_id',
        'url',
        'action',
        'api_type',
        'status_code',
        'status',
        'response_body',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'status_code' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function queue(): BelongsTo
    {
        return $this->belongsTo(SeoIndexingQueue::class, 'queue_id');
    }
}

