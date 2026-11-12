<?php

namespace App\Modules\Analytics\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitorPageview extends Model
{
    public $timestamps = false;

    protected $table = 'visitor_pageviews';

    protected $fillable = [
        'visitor_session_id',
        'url',
        'path',
        'page_title',
        'referrer_url',
        'response_time_ms',
        'viewed_at',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'response_time_ms' => 'integer',
            'viewed_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(VisitorSession::class, 'visitor_session_id');
    }
}

