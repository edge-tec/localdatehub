<?php

namespace App\Modules\Moderation\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppealRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'user_suspension_id',
        'appeal_reason',
        'status',
        'decision_notes',
        'reviewed_by_admin_id',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function suspension(): BelongsTo
    {
        return $this->belongsTo(UserSuspension::class, 'user_suspension_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_admin_id');
    }
}
