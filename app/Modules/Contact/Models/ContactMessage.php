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
        'inquiry_type',
        'reply_to_id',
        'sender_name',
        'sender_email_encrypted',
        'sender_phone_encrypted',
        'subject',
        'message_encrypted',
        'attachment_path',
        'attachment_type',
        'attachment_name',
        'attachment_size',
        'is_read',
        'read_at',
        'is_replied',
        'is_pinned',
        'is_starred',
        'is_archived',
        'reactions',
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
            'is_pinned' => 'boolean',
            'is_starred' => 'boolean',
            'is_archived' => 'boolean',
            'is_flagged' => 'boolean',
            'reactions' => 'array',
            'attachment_size' => 'integer',
            'read_at' => 'datetime',
        ];
    }

    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reply_to_id');
    }

    public function replies(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(self::class, 'reply_to_id');
    }

    public function getAttachmentUrlAttribute(): ?string
    {
        if (empty($this->attachment_path)) {
            return null;
        }
        if (str_starts_with($this->attachment_path, 'http://') || str_starts_with($this->attachment_path, 'https://')) {
            return $this->attachment_path;
        }
        if (str_starts_with($this->attachment_path, '/')) {
            return asset(ltrim($this->attachment_path, '/'));
        }
        return asset('storage/' . $this->attachment_path);
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
