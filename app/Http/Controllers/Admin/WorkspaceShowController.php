<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Membership;
use App\Models\Property;
use App\Models\Team;
use Inertia\Inertia;
use Inertia\Response;

class WorkspaceShowController extends Controller
{
    public function __invoke(Team $workspace): Response
    {
        $workspace->loadCount([
            'members',
            'properties',
            'leases',
            'renters',
            'rentPayments',
            'expenses',
        ]);

        $members = Membership::query()
            ->where('team_id', $workspace->id)
            ->with('user')
            ->orderBy('id')
            ->get()
            ->map(fn (Membership $membership): array => [
                'id' => $membership->user->id,
                'name' => $membership->user->name,
                'email' => $membership->user->email,
                'role' => $membership->role->value,
                'joined_at' => $membership->created_at,
            ]);

        $properties = $workspace->properties()
            ->withCount('leases')
            ->orderBy('name')
            ->limit(100)
            ->get([
                'id',
                'name',
                'city',
                'county_or_sector',
                'status',
                'monthly_rent_amount',
                'currency',
            ])
            ->map(fn (Property $property): array => [
                'id' => $property->id,
                'name' => $property->name,
                'city' => $property->city,
                'county_or_sector' => $property->county_or_sector,
                'status' => $property->status,
                'monthly_rent_amount' => $property->monthly_rent_amount,
                'currency' => $property->currency,
                'leases_count' => (int) $property->getAttribute('leases_count'),
            ]);

        return Inertia::render('admin/workspaces/show', [
            'workspace' => [
                'id' => $workspace->id,
                'name' => $workspace->name,
                'slug' => $workspace->slug,
                'is_personal' => $workspace->is_personal,
                'created_at' => $workspace->created_at,
            ],
            'stats' => [
                'members' => (int) $workspace->getAttribute('members_count'),
                'properties' => (int) $workspace->getAttribute('properties_count'),
                'leases' => (int) $workspace->getAttribute('leases_count'),
                'renters' => (int) $workspace->getAttribute('renters_count'),
                'payments' => (int) $workspace->getAttribute('rent_payments_count'),
                'expenses' => (int) $workspace->getAttribute('expenses_count'),
            ],
            'members' => $members,
            'properties' => $properties,
            'properties_truncated' => (int) $workspace->getAttribute('properties_count') > $properties->count(),
        ]);
    }
}
