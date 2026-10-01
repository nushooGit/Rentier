<?php

namespace App\Policies;

use App\Models\Reminder;
use App\Models\Team;
use App\Models\User;

class ReminderPolicy
{
    public function viewAny(User $user, Team $team): bool
    {
        return $user->belongsToTeam($team);
    }

    public function view(User $user, Reminder $reminder): bool
    {
        return $user->belongsToTeam($reminder->team);
    }

    public function create(User $user, Team $team): bool
    {
        return $user->belongsToTeam($team);
    }

    public function update(User $user, Reminder $reminder): bool
    {
        return $user->belongsToTeam($reminder->team);
    }

    public function delete(User $user, Reminder $reminder): bool
    {
        return $user->belongsToTeam($reminder->team);
    }
}
