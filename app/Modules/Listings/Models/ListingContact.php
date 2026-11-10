<?php

namespace App\Modules\Listings\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ListingContact extends Model
{
    use HasFactory;

    protected $fillable = [
        'listing_id',
        'allow_platform_messages',
        'allow_email_relay',
        'show_email',
        'masked_relay_email',
        'preferred_email_encrypted',
        'preferred_phone_encrypted',
        'external_handle_encrypted',
        'show_phone',
        'preferred_contact_method',
        'contact_instructions',
    ];

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
}
