<?php

namespace App\Http\Controllers;

use App\Http\Requests\Utilities\SaveUtilityAccountRequest;
use App\Models\Team;
use App\Models\UtilityAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class UtilityAccountController extends Controller
{
    public function store(
        SaveUtilityAccountRequest $request,
        Team $currentTeam,
    ): RedirectResponse {
        UtilityAccount::query()->create([
            'team_id' => $currentTeam->id,
            ...$request->safe()->only([
                'property_id',
                'lease_id',
                'provider_name',
                'service_type',
                'account_identifier',
                'responsible_party',
                'status',
                'notes',
            ]),
        ]);

        return back();
    }

    public function update(
        SaveUtilityAccountRequest $request,
        Team $currentTeam,
        UtilityAccount $utilityAccount,
    ): RedirectResponse {
        $this->abortIfOutsideWorkspace($currentTeam, $utilityAccount);

        $utilityAccount->update($request->safe()->only([
            'property_id',
            'lease_id',
            'provider_name',
            'service_type',
            'account_identifier',
            'responsible_party',
            'status',
            'notes',
        ]));

        return back();
    }

    public function destroy(
        Team $currentTeam,
        UtilityAccount $utilityAccount,
    ): RedirectResponse {
        Gate::authorize('delete', $utilityAccount);
        $this->abortIfOutsideWorkspace($currentTeam, $utilityAccount);

        if ($utilityAccount->bills()->exists()) {
            throw ValidationException::withMessages([
                'utility_account' => __('Delete this account only after its bills have been removed.'),
            ]);
        }

        $utilityAccount->delete();

        return back();
    }

    private function abortIfOutsideWorkspace(
        Team $currentTeam,
        UtilityAccount $utilityAccount,
    ): void {
        abort_unless($utilityAccount->team_id === $currentTeam->id, 404);
    }
}
