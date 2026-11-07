<?php

namespace App\Modules\Admin\Services;

use App\Modules\Admin\Models\AdminAuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AdminAuditService
{
    /**
     * Record an immutable audit log record for an administrative action.
     */
    public static function log(
        string $action,
        mixed $target = null,
        ?array $before = null,
        ?array $after = null,
        ?int $adminUserId = null
    ): AdminAuditLog {
        $targetType = null;
        $targetId = null;

        if ($target instanceof Model) {
            $targetType = get_class($target);
            $targetId = $target->getKey();
        } elseif (is_string($target)) {
            $targetType = $target;
        }

        return AdminAuditLog::create([
            'admin_user_id' => $adminUserId ?: (Auth::id() ?: 1),
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'before_state' => $before,
            'after_state' => $after,
            'ip_address' => request()->ip(),
            'user_agent' => substr((string) request()->userAgent(), 0, 255),
            'created_at' => now(),
        ]);
    }
}

