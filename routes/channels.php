<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('wallet.user.{userId}', function ($user, int $userId): bool {
    return (int) $user->id === $userId;
});

Broadcast::channel('admin.treasury', function ($user): bool {
    if (method_exists($user, 'hasAdminRole')) {
        return $user->hasAdminRole();
    }

    return method_exists($user, 'roles') && $user->roles()->exists();
});

