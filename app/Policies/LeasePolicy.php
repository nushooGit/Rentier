<?php

namespace App\Policies;

use App\Models\Lease;
use App\Models\Team;
use App\Models\User;

class LeasePolicy
{
    /**
     * Determine whether the user can view leases in the workspace.
     */
    public function viewAny(User $user, Team $team): bool
    {
        return $user->belongsToTeam($team);
    }

    /**
     * Determine whether the user can view the lease.
     */
    public function view(User $user, Lease $lease): bool
    {
        return $user->belongsToTeam($lease->team);
    }

    /**
     * Determine whether the user can create leases in the workspace.
     */
    public function create(User $user, Team $team): bool
    {
        return $user->belongsToTeam($team);
    }

    /**
     * Determine whether the user can update the lease.
     */
    public function update(User $user, Lease $lease): bool
    {
        return $user->belongsToTeam($lease->team);
    }

    /**
     * Determine whether the user can delete the lease.
     */
    public function delete(User $user, Lease $lease): bool
    {
        return $user->belongsToTeam($lease->team);
    }
}
