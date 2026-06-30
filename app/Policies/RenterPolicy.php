<?php

namespace App\Policies;

use App\Models\Renter;
use App\Models\Team;
use App\Models\User;

class RenterPolicy
{
    /**
     * Determine whether the user can view renters in the workspace.
     */
    public function viewAny(User $user, Team $team): bool
    {
        return $user->belongsToTeam($team);
    }

    /**
     * Determine whether the user can view the renter.
     */
    public function view(User $user, Renter $renter): bool
    {
        return $user->belongsToTeam($renter->team);
    }

    /**
     * Determine whether the user can create renters in the workspace.
     */
    public function create(User $user, Team $team): bool
    {
        return $user->belongsToTeam($team);
    }

    /**
     * Determine whether the user can update the renter.
     */
    public function update(User $user, Renter $renter): bool
    {
        return $user->belongsToTeam($renter->team);
    }

    /**
     * Determine whether the user can delete the renter.
     */
    public function delete(User $user, Renter $renter): bool
    {
        return $user->belongsToTeam($renter->team);
    }
}
