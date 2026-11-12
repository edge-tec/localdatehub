<?php

namespace App\Modules\Analytics\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitorActivity extends Model
{
    public $timestamps = false;

    protected $table = 'visitor_activity';

    protected $fillable = [
        'visitor_session_id',
        'event_type',
        'page_url',
        'page_title',
        'details',
        'occurred_at',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(VisitorSession::class, 'visitor_session_id');
    }
}
