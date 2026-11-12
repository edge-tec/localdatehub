<?php

namespace App\Modules\EmailRelay\Models;

use App\Modules\Listings\Models\Listing;
use App\Modules\Listings\Models\ListingContact;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailRelayConversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'listing_id',
        'relay_contact_id',
        'conversation_token',
        'reply_token_hash',
        'visitor_email_encrypted',
        'visitor_name',
        'advertiser_email_encrypted',
        'subject',
        'last_message_id',
        'status',
        'last_activity_at',
    ];

    protected function casts(): array
    {
        return [
            'visitor_email_encrypted' => 'encrypted',
            'advertiser_email_encrypted' => 'encrypted',
            'last_activity_at' => 'datetime',
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

    public function messages(): HasMany
    {
        return $this->hasMany(EmailRelayMessage::class, 'conversation_id');
    }

    public function getReplyAddressAttribute(): string
    {
        $domain = config('email_relay.domain', 'localdatehub.com');

        return 'reply-' . $this->conversation_token . '@' . $domain;
    }
}

