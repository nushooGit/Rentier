<?php

namespace App\Http\Controllers;

use App\Http\Requests\Reminders\StoreReminderRequest;
use App\Http\Requests\Reminders\UpdateReminderRequest;
use App\Models\Lease;
use App\Models\Reminder;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class ReminderController extends Controller
{
    public function store(StoreReminderRequest $request, Team $currentTeam): RedirectResponse
    {
        $lease = $request->filled('lease_id')
            ? Lease::query()
                ->whereBelongsTo($currentTeam)
                ->whereKey($request->integer('lease_id'))
                ->firstOrFail()
            : null;

        Reminder::query()->create([
            'team_id' => $currentTeam->id,
            'property_id' => $request->filled('property_id')
                ? $request->integer('property_id')
                : $lease?->property_id,
            'lease_id' => $lease?->id,
            'created_by_user_id' => $request->user()->id,
            'title' => $request->validated('title'),
            'remind_on' => $request->validated('remind_on'),
            'notes' => $request->validated('notes'),
        ]);

        return back();
    }

    public function update(
        UpdateReminderRequest $request,
        Team $currentTeam,
        Reminder $reminder,
    ): RedirectResponse {
        $this->abortIfReminderIsOutsideWorkspace($currentTeam, $reminder);

        $lease = $request->filled('lease_id')
            ? Lease::query()
                ->whereBelongsTo($currentTeam)
                ->whereKey($request->integer('lease_id'))
                ->firstOrFail()
            : null;

        $reminder->update([
            'property_id' => $request->filled('property_id')
                ? $request->integer('property_id')
                : $lease?->property_id,
            'lease_id' => $lease?->id,
            'title' => $request->validated('title'),
            'remind_on' => $request->validated('remind_on'),
            'notes' => $request->validated('notes'),
        ]);

        return back();
    }

    public function toggleComplete(Team $currentTeam, Reminder $reminder): RedirectResponse
    {
        Gate::authorize('update', $reminder);
        $this->abortIfReminderIsOutsideWorkspace($currentTeam, $reminder);

        $reminder->update([
            'completed_at' => $reminder->completed_at ? null : now(),
        ]);

        return back();
    }

    public function destroy(Team $currentTeam, Reminder $reminder): RedirectResponse
    {
        Gate::authorize('delete', $reminder);
        $this->abortIfReminderIsOutsideWorkspace($currentTeam, $reminder);

        $reminder->delete();

        return back();
    }

    private function abortIfReminderIsOutsideWorkspace(Team $currentTeam, Reminder $reminder): void
    {
        abort_unless($reminder->team_id === $currentTeam->id, 404);
    }
}
