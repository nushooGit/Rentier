<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\Team;
use App\Models\User;

class DocumentPolicy
{
    public function viewAny(User $user, Team $team): bool
    {
        return $user->belongsToTeam($team);
    }

    public function view(User $user, Document $document): bool
    {
        return $user->belongsToTeam($document->team);
    }

    public function create(User $user, Team $team): bool
    {
        return $user->belongsToTeam($team);
    }

    public function delete(User $user, Document $document): bool
    {
        return $user->belongsToTeam($document->team);
    }
}
