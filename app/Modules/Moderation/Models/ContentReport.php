<?php

namespace App\Modules\Moderation\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ContentReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'reporter_id',
        'reportable_type',
        'reportable_id',
        'reason_code',
        'details',
        'evidence_urls',
        'status',
        'resolution_notes',
        'resolved_by_user_id',
        'resolved_at',
        'reporter_ip',
    ];

    protected function casts(): array
    {
        return [
            'evidence_urls' => 'array',
            'resolved_at' => 'datetime',
        ];
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by_user_id');
    }

    public function reportable(): MorphTo
    {
        return $this->morphTo();
    }
}
