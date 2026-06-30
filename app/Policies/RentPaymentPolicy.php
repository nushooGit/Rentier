<?php

namespace App\Policies;

use App\Models\RentPayment;
use App\Models\Team;
use App\Models\User;

class RentPaymentPolicy
{
    public function viewAny(User $user, Team $team): bool
    {
        return $user->belongsToTeam($team);
    }

    public function view(User $user, RentPayment $rentPayment): bool
    {
        return $user->belongsToTeam($rentPayment->team);
    }

    public function create(User $user, Team $team): bool
    {
        return $user->belongsToTeam($team);
    }

    public function update(User $user, RentPayment $rentPayment): bool
    {
        return $user->belongsToTeam($rentPayment->team);
    }

    public function delete(User $user, RentPayment $rentPayment): bool
    {
        return $user->belongsToTeam($rentPayment->team);
    }
}
