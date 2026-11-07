<?php

namespace App\Modules\Contact\Policies;

use App\Models\User;
use App\Modules\Contact\Models\ContactMessage;
use Illuminate\Auth\Access\HandlesAuthorization;

class ContactMessagePolicy
{
    use HandlesAuthorization;

    public function before(User $user, string $ability): ?bool
    {
        if (in_array($user->role, ['admin', 'super_admin'])) {
            return true;
        }

        return null;
    }

    public function view(User $user, ContactMessage $message): bool
    {
        return $user->id === $message->recipient_id ||
               $user->id === $message->sender_id ||
               in_array($user->role, ['moderator']);
    }

    public function update(User $user, ContactMessage $message): bool
    {
        return $user->id === $message->recipient_id ||
               in_array($user->role, ['moderator']);
    }

    public function delete(User $user, ContactMessage $message): bool
    {
        return $user->id === $message->recipient_id;
    }
}

