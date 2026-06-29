<?php

namespace App\Policies;

use App\Models\Property;
use App\Models\Team;
use App\Models\User;

class PropertyPolicy
{
    /**
     * Determine whether the user can view any properties in the workspace.
     */
    public function viewAny(User $user, Team $team): bool
    {
        return $user->belongsToTeam($team);
    }

    /**
     * Determine whether the user can view the property.
     */
    public function view(User $user, Property $property): bool
    {
        return $user->belongsToTeam($property->team);
    }

    /**
     * Determine whether the user can create properties in the workspace.
     */
    public function create(User $user, Team $team): bool
    {
        return $user->belongsToTeam($team);
    }

    /**
     * Determine whether the user can update the property.
     */
    public function update(User $user, Property $property): bool
    {
        return $user->belongsToTeam($property->team);
    }

    /**
     * Determine whether the user can delete the property.
     */
    public function delete(User $user, Property $property): bool
    {
        return $user->belongsToTeam($property->team);
    }
}
