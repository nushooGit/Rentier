<?php

namespace App\Http\Controllers\Renters;

use App\Http\Controllers\Controller;
use App\Models\Lease;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RenterPortalController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        $leases = Lease::query()
            ->with('property')
            ->whereHas('renter', fn ($query) => $query
                ->where('user_id', $user->id)
                ->whereHas('portalInvitations', fn ($invitation) => $invitation
                    ->whereNotNull('accepted_at')
                    ->whereNull('revoked_at')
                    ->where('accepted_by_user_id', $user->id)
                    ->where('email', $user->email)
                    ->whereRaw('LOWER(renter_invitations.email) = LOWER(renters.email)')))
            ->whereHas('team', fn ($query) => $query->whereNull('suspended_at'))
            ->orderByDesc('start_date')
            ->get()
            ->map(fn (Lease $lease) => [
                'id' => $lease->id,
                'property' => $lease->property->name,
                'startDate' => $lease->start_date->toDateString(),
                'endDate' => $lease->end_date?->toDateString(),
                'rent' => $lease->monthly_rent_amount,
                'currency' => $lease->currency,
                'dueDay' => $lease->rent_due_day,
                'status' => $lease->computedStatus(),
            ]);

        return Inertia::render('renters/overview', ['leases' => $leases]);
    }
}
