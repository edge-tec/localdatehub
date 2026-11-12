<?php

namespace App\Modules\EmailRelay\Models;

use App\Modules\Listings\Models\Listing;
use App\Modules\Listings\Models\ListingContact;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailRelayMessage extends Model
{
    use HasFactory;

    public const DIRECTION_INBOUND = 'inbound';
    public const DIRECTION_OUTBOUND = 'outbound';

    public const STATUS_RECEIVED = 'received';
    public const STATUS_QUEUED = 'queued';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_BOUNCED = 'bounced';
    public const STATUS_FAILED = 'failed';
    public const STATUS_BLOCKED = 'blocked';

    protected $fillable = [
        'listing_id',
        'relay_contact_id',
        'conversation_id',
        'direction',
        'sender_name',
        'sender_email_encrypted',
        'recipient_email_encrypted',
        'subject',
        'message_body_encrypted',
        'provider',
        'provider_message_id',
        'message_id',
        'in_reply_to',
        'references_header',
        'attachments_meta',
        'delivery_status',
        'spam_score',
        'error_message',
        'sender_ip',
        'received_at',
        'sent_at',
        'delivered_at',
        'failed_at',
    ];

    protected function casts(): array
    {
        return [
            'sender_email_encrypted' => 'encrypted',
            'recipient_email_encrypted' => 'encrypted',
            'message_body_encrypted' => 'encrypted',
            'attachments_meta' => 'array',
            'spam_score' => 'integer',
            'received_at' => 'datetime',
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(ListingContact::class, 'relay_contact_id');
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(EmailRelayConversation::class, 'conversation_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(EmailRelayEvent::class, 'relay_message_id');
    }
}

