<?php

namespace App\Http\Controllers\Renters;

use App\Actions\Renters\ManageRenterInvitation;
use App\Http\Controllers\Controller;
use App\Models\Renter;
use App\Models\RenterInvitation;
use App\Models\Team;
use App\Notifications\Renters\RenterPortalInvitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class RenterInvitationController extends Controller
{
    public function store(Request $request, Team $currentTeam, Renter $renter, ManageRenterInvitation $action): RedirectResponse
    {
        abort_unless($renter->team_id === $currentTeam->id, 404);
        $issued = $action->issue($request->user(), $renter);

        Notification::route('mail', $issued['invitation']->email)
            ->notify(new RenterPortalInvitation($issued['token']));

        return back();
    }

    public function destroy(Request $request, Team $currentTeam, RenterInvitation $invitation, ManageRenterInvitation $action): RedirectResponse
    {
        abort_unless($invitation->team_id === $currentTeam->id, 404);
        $action->revoke($request->user(), $invitation);

        return back();
    }

    public function accept(Request $request, ManageRenterInvitation $action): RedirectResponse
    {
        $request->validate(['token' => ['required', 'string', 'size:64']]);
        $action->accept($request->user(), $request->string('token')->toString());

        return redirect()->route('renter.overview');
    }
}
