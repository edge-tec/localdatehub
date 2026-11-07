<?php

namespace App\Modules\Moderation\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModerationAction extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'moderation_queue_id',
        'admin_user_id',
        'action_taken',
        'reason',
        'internal_notes',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function queueItem(): BelongsTo
    {
        return $this->belongsTo(ModerationQueue::class, 'moderation_queue_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }
}
