<?php

namespace App\Modules\Accounts\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgeAssurance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'method',
        'status',
        'declared_birthdate',
        'document_hash',
        'third_party_reference',
        'verification_metadata',
        'reviewed_by_admin_id',
        'verified_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'declared_birthdate' => 'date',
            'verification_metadata' => 'array',
            'verified_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_admin_id');
    }
}
