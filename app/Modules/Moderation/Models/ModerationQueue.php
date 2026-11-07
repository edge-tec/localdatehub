<?php

namespace App\Modules\Moderation\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ModerationQueue extends Model
{
    use HasFactory;

    public $table = 'moderation_queue';

    protected $fillable = [
        'moderatable_type',
        'moderatable_id',
        'status',
        'trigger_reason',
        'automated_flags',
        'assigned_to_user_id',
        'reviewer_notes',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'automated_flags' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    public function moderatable(): MorphTo
    {
        return $this->morphTo();
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function actions(): HasMany
    {
        return $this->hasMany(ModerationAction::class);
    }
}
