<?php

namespace App\Modules\Verification\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VerificationAuditLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'verification_audit_logs';

    protected $fillable = [
        'verification_id',
        'actor_id',
        'action',
        'details',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function verification(): BelongsTo
    {
        return $this->belongsTo(IdentityVerification::class, 'verification_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}

