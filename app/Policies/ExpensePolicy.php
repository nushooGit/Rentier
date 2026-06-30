<?php

namespace App\Policies;

use App\Models\Expense;
use App\Models\Team;
use App\Models\User;

class ExpensePolicy
{
    public function viewAny(User $user, Team $team): bool
    {
        return $user->belongsToTeam($team);
    }

    public function view(User $user, Expense $expense): bool
    {
        return $user->belongsToTeam($expense->team);
    }

    public function create(User $user, Team $team): bool
    {
        return $user->belongsToTeam($team);
    }

    public function update(User $user, Expense $expense): bool
    {
        return $user->belongsToTeam($expense->team);
    }

    public function delete(User $user, Expense $expense): bool
    {
        return $user->belongsToTeam($expense->team);
    }
}
