<?php

namespace App\Policies;

use App\Models\Team;
use App\Models\User;
use App\Models\UtilityAccount;

class UtilityAccountPolicy
{
    public function viewAny(User $user, Team $team): bool
    {
        return $user->belongsToTeam($team);
    }

    public function view(User $user, UtilityAccount $utilityAccount): bool
    {
        return $user->belongsToTeam($utilityAccount->team);
    }

    public function create(User $user, Team $team): bool
    {
        return $user->belongsToTeam($team);
    }

    public function update(User $user, UtilityAccount $utilityAccount): bool
    {
        return $user->belongsToTeam($utilityAccount->team);
    }

    public function delete(User $user, UtilityAccount $utilityAccount): bool
    {
        return $user->belongsToTeam($utilityAccount->team);
    }
}
