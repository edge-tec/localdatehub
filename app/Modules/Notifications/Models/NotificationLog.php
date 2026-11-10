<?php

namespace App\Modules\Notifications\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationLog extends Model
{
    use HasFactory;

    public $table = 'notifications_log';

    protected $fillable = [
        'user_id',
        'channel',
        'notification_type',
        'subject',
        'message',
        'recipient_target',
        'status',
        'is_read',
        'read_at',
        'error_details',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
            'read_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    public function markAsRead(): void
    {
        if (!$this->is_read) {
            $this->is_read = true;
            $this->read_at = now();
            $this->save();
        }
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
