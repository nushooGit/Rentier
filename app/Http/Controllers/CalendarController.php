<?php

namespace App\Http\Controllers;

use App\Models\Lease;
use App\Models\Team;
use App\Services\RentPaymentAllocationCalculator;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class CalendarController extends Controller
{
    public function __invoke(
        Request $request,
        Team $currentTeam,
        RentPaymentAllocationCalculator $allocationCalculator,
    ): Response {
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
                        'lease_id' => $lease->id,
                        'property_id' => $lease->property_id,
                        'property_name' => $lease->property->name,
                        'property_city' => $lease->property->city,
                        'renter_name' => $lease->renter->name,
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

        $priority = [
            'lease_start' => 0,
            'rent_due' => 1,
            'lease_end' => 2,
        ];

        $events = $events
            ->sortBy(fn (array $event) => sprintf(
                '%s-%02d-%010d',
                $event['date'],
                $priority[$event['kind']] ?? 9,
                $event['lease_id'],
            ))
            ->values();

        return Inertia::render('calendar/index', [
            'selectedMonth' => $periodKey,
            'previousMonth' => $selectedMonth->copy()->subMonthNoOverflow()->format('Y-m'),
            'nextMonth' => $selectedMonth->copy()->addMonthNoOverflow()->format('Y-m'),
            'todayMonth' => $today->format('Y-m'),
            'today' => $today->toDateString(),
            'events' => $events,
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
            ],
        ]);
    }

    /**
     * @return array{
     *     id: string,
     *     kind: string,
     *     date: string,
     *     lease_id: int,
     *     property_id: int,
     *     property_name: string,
     *     property_city: string,
     *     renter_name: string,
     *     amount: null,
     *     currency: string,
     *     remaining_amount: null,
     *     status_key: null
     * }
     */
    private function leaseEvent(string $kind, Lease $lease, CarbonInterface $date): array
    {
        return [
            'id' => "{$kind}-{$lease->id}",
            'kind' => $kind,
            'date' => $date->toDateString(),
            'lease_id' => $lease->id,
            'property_id' => $lease->property_id,
            'property_name' => $lease->property->name,
            'property_city' => $lease->property->city,
            'renter_name' => $lease->renter->name,
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
