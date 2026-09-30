<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SuspendResourceRequest;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class WorkspaceSuspensionController extends Controller
{
    public function suspend(SuspendResourceRequest $request, Team $workspace): RedirectResponse
    {
        DB::transaction(function () use ($request, $workspace): void {
            $lockedWorkspace = Team::query()->lockForUpdate()->findOrFail($workspace->id);

            abort_if($lockedWorkspace->isSuspended(), 409, 'Workspace-ul este deja suspendat.');

            $lockedWorkspace->forceFill([
                'suspended_at' => now(),
                'suspension_reason' => $request->validated('reason'),
                'suspended_by_user_id' => $request->user()->id,
                'reactivated_at' => null,
                'reactivated_by_user_id' => null,
            ])->save();
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Workspace-ul a fost suspendat.',
        ]);

        return back();
    }

    public function reactivate(Request $request, Team $workspace): RedirectResponse
    {
        DB::transaction(function () use ($request, $workspace): void {
            $lockedWorkspace = Team::query()->lockForUpdate()->findOrFail($workspace->id);

            abort_unless($lockedWorkspace->isSuspended(), 409, 'Workspace-ul este deja activ.');

            $lockedWorkspace->forceFill([
                'suspended_at' => null,
                'reactivated_at' => now(),
                'reactivated_by_user_id' => $request->user()->id,
            ])->save();
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Workspace-ul a fost reactivat.',
        ]);

        return back();
    }
}
