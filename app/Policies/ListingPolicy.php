<?php

namespace App\Policies;

use App\Models\User;
use App\Modules\Listings\Models\Listing;

class ListingPolicy
{
    /**
     * Determine whether the user can view the listing.
     */
    public function view(?User $user, Listing $listing): bool
    {
        if ($listing->isActive()) {
            return true;
        }

        if (!$user) {
            return false;
        }

        return $user->id === $listing->user_id || in_array($user->role, ['admin', 'moderator'], true);
    }

    /**
     * Determine whether the user can create listings.
     */
    public function create(User $user): bool
    {
        return !$user->isSuspended();
    }

    /**
     * Determine whether the user can update the listing.
     */
    public function update(User $user, Listing $listing): bool
    {
        if (in_array($user->role, ['admin', 'moderator'], true)) {
            return true;
        }

        return $user->id === $listing->user_id && !$user->isSuspended();
    }

    /**
     * Determine whether the user can delete the listing.
     */
    public function delete(User $user, Listing $listing): bool
    {
        if (in_array($user->role, ['admin', 'moderator'], true)) {
            return true;
        }

        return $user->id === $listing->user_id;
    }

    /**
     * Determine whether the user can renew the listing.
     */
    public function renew(User $user, Listing $listing): bool
    {
        if (in_array($user->role, ['admin', 'moderator'], true)) {
            return true;
        }

        return $user->id === $listing->user_id && !$user->isSuspended() && $listing->canBeRenewed();
    }

    /**
     * Determine whether the user can moderate, approve, or reject the listing.
     */
    public function moderate(User $user, Listing $listing): bool
    {
        return in_array($user->role, ['admin', 'moderator'], true);
    }
}
