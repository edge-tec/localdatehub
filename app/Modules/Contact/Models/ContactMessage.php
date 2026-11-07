<?php

namespace App\Modules\Contact\Models;

use App\Models\User;
use App\Modules\Listings\Models\Listing;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContactMessage extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'listing_id',
        'sender_id',
        'recipient_id',
        'sender_name',
        'sender_email_encrypted',
        'sender_phone_encrypted',
        'subject',
        'message_encrypted',
        'is_read',
        'read_at',
        'is_replied',
        'is_flagged',
        'flagged_reason',
        'client_ip',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'sender_email_encrypted' => 'encrypted',
            'sender_phone_encrypted' => 'encrypted',
            'message_encrypted' => 'encrypted',
            'is_read' => 'boolean',
            'is_replied' => 'boolean',
            'is_flagged' => 'boolean',
            'read_at' => 'datetime',
        ];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->where('is_read', false);
    }
}
