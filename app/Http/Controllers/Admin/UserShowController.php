<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Membership;
use App\Models\User;
use App\Support\PlatformAdmin;
use Inertia\Inertia;
use Inertia\Response;

class UserShowController extends Controller
{
    public function __invoke(User $user): Response
    {
        $user->load(['suspendedBy', 'reactivatedBy']);

        $currentWorkspace = $user->currentTeam()->first();

        $memberships = $user->teamMemberships()
            ->with('team')
            ->orderBy('team_id')
            ->get()
            ->map(fn (Membership $membership): array => [
                'workspace' => [
                    'id' => $membership->team->id,
                    'name' => $membership->team->name,
                    'slug' => $membership->team->slug,
                    'is_personal' => $membership->team->is_personal,
                    'is_suspended' => $membership->team->isSuspended(),
                ],
                'role' => $membership->role->value,
                'created_at' => $membership->created_at,
            ]);

        return Inertia::render('admin/users/show', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at,
                'created_at' => $user->created_at,
                'is_platform_admin' => PlatformAdmin::allows($user),
                'two_factor_enabled' => $user->two_factor_confirmed_at !== null,
                'current_workspace' => $currentWorkspace ? [
                    'id' => $currentWorkspace->id,
                    'name' => $currentWorkspace->name,
                    'slug' => $currentWorkspace->slug,
                ] : null,
                'suspended_at' => $user->suspended_at,
                'suspension_reason' => $user->suspension_reason,
                'suspended_by' => $user->suspendedBy ? [
                    'id' => $user->suspendedBy->id,
                    'name' => $user->suspendedBy->name,
                ] : null,
                'reactivated_at' => $user->reactivated_at,
                'reactivated_by' => $user->reactivatedBy ? [
                    'id' => $user->reactivatedBy->id,
                    'name' => $user->reactivatedBy->name,
                ] : null,
                'can_suspend' => ! PlatformAdmin::allows($user),
            ],
            'stats' => [
                'workspaces' => $user->teams()->count(),
                'owned_workspaces' => $user->ownedTeams()->count(),
            ],
            'memberships' => $memberships,
        ]);
    }
}
