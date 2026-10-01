<?php

namespace App\Policies;

use App\Models\Team;
use App\Models\User;
use App\Models\UtilityBill;

class UtilityBillPolicy
{
    public function viewAny(User $user, Team $team): bool
    {
        return $user->belongsToTeam($team);
    }

    public function view(User $user, UtilityBill $utilityBill): bool
    {
        return $user->belongsToTeam($utilityBill->team);
    }

    public function create(User $user, Team $team): bool
    {
        return $user->belongsToTeam($team);
    }

    public function update(User $user, UtilityBill $utilityBill): bool
    {
        return $user->belongsToTeam($utilityBill->team);
    }

    public function delete(User $user, UtilityBill $utilityBill): bool
    {
        return $user->belongsToTeam($utilityBill->team);
    }
}
