<?php

namespace App\Policies;

use App\Models\User;
use App\Modules\Listings\Models\Listing;

class ListingPolicy
{
    protected function canModerateListings(User $user): bool
    {
        return in_array($user->role, [
            'super_admin',
            'admin',
            'moderator',
            'senior_moderator',
            'listing_moderator',
        ], true) || $user->hasPermission('listings.moderate');
    }

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

        return $user->id === $listing->user_id || $this->canModerateListings($user);
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
        if ($this->canModerateListings($user)) {
            return true;
        }

        return $user->id === $listing->user_id && !$user->isSuspended();
    }

    /**
     * Determine whether the user can delete the listing.
     */
    public function delete(User $user, Listing $listing): bool
    {
        if ($this->canModerateListings($user)) {
            return true;
        }

        return $user->id === $listing->user_id;
    }

    /**
     * Determine whether the user can renew the listing.
     */
    public function renew(User $user, Listing $listing): bool
    {
        if ($this->canModerateListings($user)) {
            return true;
        }

        return $user->id === $listing->user_id && !$user->isSuspended() && $listing->canBeRenewed();
    }

    /**
     * Determine whether the user can moderate, approve, or reject the listing.
     */
    public function moderate(User $user, Listing $listing): bool
    {
        return $this->canModerateListings($user);
    }
}

