<?php

namespace App\Http\Controllers;

use App\Models\Lease;
use App\Models\Property;
use App\Models\Reminder;
use App\Models\Team;
use App\Services\RentPaymentAllocationCalculator;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CalendarController extends Controller
{
    public function __invoke(
        Request $request,
        Team $currentTeam,
        RentPaymentAllocationCalculator $allocationCalculator,
    ): Response {
        Gate::authorize('viewAny', [Reminder::class, $currentTeam]);

        $validated = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
        ]);

        $timezone = config('app.timezone');
        $today = Carbon::today($timezone);
        $selectedMonth = isset($validated['month'])
            ? Carbon::createFromFormat('Y-m-d', $validated['month'].'-01', $timezone)->startOfMonth()
            : $today->copy()->startOfMonth();
        $monthEnd = $selectedMonth->copy()->endOfMonth()->startOfDay();
        $referenceDate = $today->lessThan($selectedMonth)
            ? $selectedMonth->copy()
            : ($today->greaterThan($monthEnd) ? $monthEnd->copy() : $today->copy());

        $leases = Lease::query()
            ->with([
                'property:id,team_id,name,city',
                'renter:id,team_id,name',
            ])
            ->whereBelongsTo($currentTeam)
            ->whereDate('start_date', '<=', $monthEnd)
            ->where(function ($query) use ($selectedMonth) {
                $query
                    ->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $selectedMonth);
            })
            ->orderBy('start_date')
            ->orderBy('id')
            ->get();

        $events = collect();
        $periodKey = $selectedMonth->format('Y-m');

        foreach ($leases as $lease) {
            if (
                $lease->start_date->greaterThanOrEqualTo($selectedMonth)
                && $lease->start_date->lessThanOrEqualTo($monthEnd)
            ) {
                $events->push($this->leaseEvent(
                    kind: 'lease_start',
                    lease: $lease,
                    date: $lease->start_date,
                ));
            }

            if ($lease->rent_due_day !== null && (float) $lease->monthly_rent_amount > 0) {
                $allocation = $allocationCalculator->forLease($lease, $referenceDate);
                $month = $allocation['months'][$periodKey] ?? null;

                if (is_array($month)) {
                    $events->push([
                        'id' => "rent-due-{$lease->id}-{$periodKey}",
                        'kind' => 'rent_due',
                        'date' => $month['due_date'],
                        'reminder_id' => null,
                        'lease_id' => $lease->id,
                        'property_id' => $lease->property_id,
                        'property_name' => $lease->property->name,
                        'property_city' => $lease->property->city,
                        'renter_name' => $lease->renter->name,
                        'title' => null,
                        'notes' => null,
                        'completed' => false,
                        'amount' => $month['expected_amount'],
                        'currency' => $lease->currency,
                        'remaining_amount' => $month['remaining_amount'],
                        'status_key' => $this->rentStatusKey($month, $referenceDate),
                    ]);
                }
            }

            if (
                $lease->end_date !== null
                && $lease->end_date->greaterThanOrEqualTo($selectedMonth)
                && $lease->end_date->lessThanOrEqualTo($monthEnd)
            ) {
                $events->push($this->leaseEvent(
                    kind: 'lease_end',
                    lease: $lease,
                    date: $lease->end_date,
                ));
            }
        }

        $reminders = Reminder::query()
            ->whereBelongsTo($currentTeam)
            ->whereBetween('remind_on', [
                $selectedMonth->toDateString(),
                $monthEnd->toDateString(),
            ])
            ->with([
                'property:id,team_id,name,city',
                'lease.property:id,team_id,name,city',
                'lease.renter:id,team_id,name',
            ])
            ->orderBy('remind_on')
            ->orderBy('id')
            ->get();

        foreach ($reminders as $reminder) {
            $property = $reminder->property ?? $reminder->lease?->property;

            $events->push([
                'id' => "reminder-{$reminder->id}",
                'kind' => 'reminder',
                'date' => $reminder->remind_on->toDateString(),
                'reminder_id' => $reminder->id,
                'lease_id' => $reminder->lease_id,
                'property_id' => $property?->id,
                'property_name' => $property?->name,
                'property_city' => $property?->city,
                'renter_name' => $reminder->lease?->renter?->name,
                'title' => $reminder->title,
                'notes' => $reminder->notes,
                'completed' => $reminder->completed_at !== null,
                'amount' => null,
                'currency' => null,
                'remaining_amount' => null,
                'status_key' => null,
            ]);
        }

        $priority = [
            'lease_start' => 0,
            'rent_due' => 1,
            'reminder' => 2,
            'lease_end' => 3,
        ];

        $events = $events
            ->sortBy(fn (array $event) => sprintf(
                '%s-%02d-%010d',
                $event['date'],
                $priority[$event['kind']] ?? 9,
                $event['lease_id'] ?? $event['reminder_id'] ?? 0,
            ))
            ->values();

        return Inertia::render('calendar/index', [
            'selectedMonth' => $periodKey,
            'previousMonth' => $selectedMonth->copy()->subMonthNoOverflow()->format('Y-m'),
            'nextMonth' => $selectedMonth->copy()->addMonthNoOverflow()->format('Y-m'),
            'todayMonth' => $today->format('Y-m'),
            'today' => $today->toDateString(),
            'events' => $events,
            'properties' => Property::query()
                ->whereBelongsTo($currentTeam)
                ->orderBy('name')
                ->get(['id', 'name', 'city']),
            'leases' => Lease::query()
                ->whereBelongsTo($currentTeam)
                ->with(['property:id,name', 'renter:id,name'])
                ->latest('start_date')
                ->get()
                ->map(fn (Lease $lease): array => [
                    'id' => $lease->id,
                    'property_id' => $lease->property_id,
                    'label' => $lease->property->name.' · '.$lease->renter->name,
                ]),
            'summary' => [
                'event_count' => $events->count(),
                'rent_due_count' => $events->where('kind', 'rent_due')->count(),
                'overdue_count' => $events
                    ->where('kind', 'rent_due')
                    ->filter(fn (array $event) => in_array(
                        $event['status_key'],
                        ['overdue', 'partial_overdue'],
                        true,
                    ))
                    ->count(),
                'lease_change_count' => $events
                    ->filter(fn (array $event) => in_array(
                        $event['kind'],
                        ['lease_start', 'lease_end'],
                        true,
                    ))
                    ->count(),
                'reminder_count' => $events->where('kind', 'reminder')->count(),
                'open_reminder_count' => $events
                    ->where('kind', 'reminder')
                    ->where('completed', false)
                    ->count(),
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function leaseEvent(string $kind, Lease $lease, CarbonInterface $date): array
    {
        return [
            'id' => "{$kind}-{$lease->id}",
            'kind' => $kind,
            'date' => $date->toDateString(),
            'reminder_id' => null,
            'lease_id' => $lease->id,
            'property_id' => $lease->property_id,
            'property_name' => $lease->property->name,
            'property_city' => $lease->property->city,
            'renter_name' => $lease->renter->name,
            'title' => null,
            'notes' => null,
            'completed' => false,
            'amount' => null,
            'currency' => $lease->currency,
            'remaining_amount' => null,
            'status_key' => null,
        ];
    }

    /**
     * @param  array{
     *     fully_paid: bool,
     *     partial: bool,
     *     overdue: bool,
     *     due_date: string
     * }  $month
     */
    private function rentStatusKey(array $month, CarbonInterface $referenceDate): string
    {
        if ($month['fully_paid']) {
            return 'paid';
        }

        if ($month['partial']) {
            return $month['overdue'] ? 'partial_overdue' : 'partial';
        }

        if ($month['overdue']) {
            return 'overdue';
        }

        $dueDate = Carbon::parse($month['due_date'], config('app.timezone'))->startOfDay();

        return $referenceDate->isSameDay($dueDate) ? 'due_today' : 'upcoming';
    }
}
