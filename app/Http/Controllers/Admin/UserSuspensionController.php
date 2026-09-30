<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SuspendResourceRequest;
use App\Models\User;
use App\Services\UserSessionInvalidator;
use App\Support\PlatformAdmin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class UserSuspensionController extends Controller
{
    public function suspend(
        SuspendResourceRequest $request,
        User $user,
        UserSessionInvalidator $sessionInvalidator,
    ): RedirectResponse {
        abort_if(
            PlatformAdmin::allows($user),
            422,
            'Un platform admin nu poate fi suspendat din interfața de administrare.',
        );

        $user = DB::transaction(function () use ($request, $user): User {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);

            abort_if($lockedUser->isSuspended(), 409, 'Contul este deja suspendat.');

            $lockedUser->forceFill([
                'suspended_at' => now(),
                'suspension_reason' => $request->validated('reason'),
                'suspended_by_user_id' => $request->user()->id,
                'reactivated_at' => null,
                'reactivated_by_user_id' => null,
            ])->save();

            return $lockedUser;
        });

        $sessionInvalidator->invalidate($user);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Contul a fost suspendat.',
        ]);

        return back();
    }

    public function reactivate(Request $request, User $user): RedirectResponse
    {
        DB::transaction(function () use ($request, $user): void {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);

            abort_unless($lockedUser->isSuspended(), 409, 'Contul este deja activ.');

            $lockedUser->forceFill([
                'suspended_at' => null,
                'reactivated_at' => now(),
                'reactivated_by_user_id' => $request->user()->id,
            ])->save();
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Contul a fost reactivat.',
        ]);

        return back();
    }
}
