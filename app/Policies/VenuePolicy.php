<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Venue;

class VenuePolicy
{
    /**
     * A user may view/manage a venue only if they own it.
     */
    public function view(User $user, Venue $venue): bool
    {
        return $user->id === $venue->owner_id;
    }

    public function update(User $user, Venue $venue): bool
    {
        return $user->id === $venue->owner_id;
    }

    public function delete(User $user, Venue $venue): bool
    {
        return $user->id === $venue->owner_id;
    }
}
