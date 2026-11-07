<?php

namespace App\Modules\Accounts\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'notification_settings',
        'privacy_settings',
        'search_defaults',
        'allow_contact_messages',
        'require_verified_senders',
    ];

    protected function casts(): array
    {
        return [
            'notification_settings' => 'array',
            'privacy_settings' => 'array',
            'search_defaults' => 'array',
            'allow_contact_messages' => 'boolean',
            'require_verified_senders' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
