<?php

namespace App\Modules\Listings\Models;

use App\Modules\EmailRelay\Models\EmailRelayConversation;
use App\Modules\EmailRelay\Models\EmailRelayMessage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ListingContact extends Model
{
    use HasFactory;

    protected $fillable = [
        'listing_id',
        'allow_platform_messages',
        'allow_email_relay',
        'show_email',
        'masked_relay_email',
        'relay_token_hash',
        'relay_status',
        'preferred_email_encrypted',
        'preferred_phone_encrypted',
        'external_handle_encrypted',
        'show_phone',
        'preferred_contact_method',
        'contact_instructions',
    ];

    protected static function booted(): void
    {
        static::saving(function (ListingContact $contact): void {
            if (!empty($contact->masked_relay_email)) {
                $normalized = strtolower(trim((string) $contact->masked_relay_email));
                $contact->masked_relay_email = $normalized;
                $contact->relay_token_hash = hash('sha256', $normalized);
            }

            if (empty($contact->relay_status)) {
                $contact->relay_status = $contact->allow_email_relay ? 'active' : 'disabled';
            }
        });
    }

    protected function casts(): array
    {
        return [
            'allow_platform_messages' => 'boolean',
            'allow_email_relay' => 'boolean',
            'show_email' => 'boolean',
            'show_phone' => 'boolean',
            'preferred_email_encrypted' => 'encrypted',
            'preferred_phone_encrypted' => 'encrypted',
            'external_handle_encrypted' => 'encrypted',
        ];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    public function relayConversations(): HasMany
    {
        return $this->hasMany(EmailRelayConversation::class, 'relay_contact_id');
    }

    public function relayMessages(): HasMany
    {
        return $this->hasMany(EmailRelayMessage::class, 'relay_contact_id');
    }
}
