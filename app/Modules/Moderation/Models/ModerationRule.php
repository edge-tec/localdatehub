<?php

namespace App\Modules\Moderation\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModerationRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'rule_type',
        'pattern',
        'action_severity',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
