<?php

namespace App\Modules\EmailRelay\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailRelayEvent extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'relay_message_id',
        'event_type',
        'provider_event_id',
        'event_payload',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'event_payload' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(EmailRelayMessage::class, 'relay_message_id');
    }
}
