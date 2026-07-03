<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Lease;
use App\Models\Property;
use App\Models\RentPayment;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Services\LeaseRentStatusCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, Team $currentTeam, LeaseRentStatusCalculator $rentStatusCalculator): Response
    {
        $email = strtolower($request->user()->email);
        $today = Carbon::today();
        $currentMonth = (int) $today->month;
        $currentYear = (int) $today->year;

        $pendingInvitations = TeamInvitation::query()
            ->with(['inviter', 'team'])
            ->whereRaw('LOWER(email) = ?', [$email])
            ->whereNull('accepted_at')
            ->where(fn ($query) => $query
                ->whereNull('expires_at')
                ->orWhere('expires_at', '>=', now()))
            ->latest()
            ->get()
            ->map(fn (TeamInvitation $invitation) => [
                'code' => $invitation->code,
                'inviterName' => $invitation->inviter->name,
                'team' => [
                    'name' => $invitation->team->name,
                    'slug' => $invitation->team->slug,
                ],
            ]);

        $properties = Property::query()
            ->whereBelongsTo($currentTeam)
            ->get();

        $propertyCount = $properties->count();
        $occupiedPropertyCount = $properties
            ->filter(fn (Property $property) => $property->isOccupied($today))
            ->count();

        $activeLeases = Lease::query()
            ->with(['property', 'renter'])
            ->whereBelongsTo($currentTeam)
            ->whereDate('start_date', '<=', $today)
            ->where(function ($query) use ($today) {
                $query
                    ->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $today);
            })
            ->orderBy('rent_due_day')
            ->get();

        $activeLeaseCount = $activeLeases->count();

        $leaseFinancialRows = $activeLeases
            ->map(function (Lease $lease) use ($rentStatusCalculator, $today) {
                $status = $rentStatusCalculator->forLease($lease, $today);

                return [
                    'lease_id' => $lease->id,
                    'property_id' => $lease->property_id,
                    'property_name' => $lease->property->name,
                    'renter_name' => $lease->renter->name,
                    'monthly_rent_amount' => $lease->monthly_rent_amount,
                    'currency' => $lease->currency,
                    'rent_due_day' => $lease->rent_due_day,
                    'due_date' => $status['due_date'],
                    'status_key' => $status['key'],
                    'status_label' => $status['label'],
                    'days' => $status['days'],
                    'expected_amount' => $status['expected_amount'],
                    'collected_amount' => $status['collected_amount'],
                    'rent_deduction_amount' => $status['rent_deduction_amount'],
                    'covered_amount' => $status['covered_amount'],
                    'remaining_amount' => $status['remaining_amount'],
                ];
            })
            ->values();

        $estimatedMonthlyRent = $leaseFinancialRows->sum(fn (array $row) => (float) $row['expected_amount']);
        $currentMonthPayments = $leaseFinancialRows->sum(fn (array $row) => (float) $row['collected_amount']);
        $currentMonthRentDeductions = $leaseFinancialRows->sum(fn (array $row) => (float) $row['rent_deduction_amount']);
        $currentMonthCoveredRent = $leaseFinancialRows->sum(fn (array $row) => (float) $row['covered_amount']);
        $remainingRent = $leaseFinancialRows->sum(fn (array $row) => (float) $row['remaining_amount']);

        $overdueLeases = $leaseFinancialRows
            ->filter(fn (array $row) => $row['status_key'] === 'overdue')
            ->values();

        $upcomingPayments = $leaseFinancialRows
            ->filter(function (array $row) use ($today) {
                if ((float) $row['remaining_amount'] <= 0) {
                    return false;
                }

                $dueDate = Carbon::parse($row['due_date']);

                return $dueDate->isSameDay($today)
                    || ($dueDate->isAfter($today) && $today->diffInDays($dueDate) <= 7);
            })
            ->values();

        $propertiesWithoutActiveLease = $properties
            ->filter(fn (Property $property) => ! $property->isOccupied($today))
            ->sortBy('name')
            ->values()
            ->map(fn (Property $property) => [
                'id' => $property->id,
                'name' => $property->name,
                'city' => $property->city,
                'address_line' => $property->address_line,
                'monthly_rent_amount' => $property->monthly_rent_amount,
                'currency' => $property->currency,
            ]);

        $ownerSupportedExpenses = Expense::query()
            ->whereBelongsTo($currentTeam)
            ->where('status', '!=', 'cancelled')
            ->where('responsible_party', 'owner')
            ->whereYear('expense_date', $currentYear)
            ->whereMonth('expense_date', $currentMonth)
            ->sum('amount');

        $tenantReimbursementExpenses = Expense::query()
            ->whereBelongsTo($currentTeam)
            ->where('status', '!=', 'cancelled')
            ->where('paid_by', 'tenant')
            ->where('responsible_party', 'owner')
            ->where('settlement_type', 'reimburse')
            ->whereYear('expense_date', $currentYear)
            ->whereMonth('expense_date', $currentMonth)
            ->sum('amount');

        $utilityDeductionExpenses = Expense::query()
            ->whereBelongsTo($currentTeam)
            ->where('status', '!=', 'cancelled')
            ->where('paid_by', 'tenant')
            ->where('responsible_party', 'owner')
            ->where('settlement_type', 'deduct_from_utilities')
            ->whereYear('expense_date', $currentYear)
            ->whereMonth('expense_date', $currentMonth)
            ->sum('amount');

        $unsettledTenantPaidOwnerExpenses = Expense::query()
            ->whereBelongsTo($currentTeam)
            ->where('status', '!=', 'cancelled')
            ->where('paid_by', 'tenant')
            ->where('responsible_party', 'owner')
            ->where('settlement_type', 'none')
            ->whereYear('expense_date', $currentYear)
            ->whereMonth('expense_date', $currentMonth)
            ->sum('amount');

        $recoverableExpenses = Expense::query()
            ->whereBelongsTo($currentTeam)
            ->where('status', '!=', 'cancelled')
            ->where('paid_by', 'owner')
            ->where('responsible_party', 'tenant')
            ->where('settlement_type', 'reimburse')
            ->whereYear('expense_date', $currentYear)
            ->whereMonth('expense_date', $currentMonth)
            ->sum('amount');

        $recentLeases = Lease::query()
            ->with(['property', 'renter'])
            ->whereBelongsTo($currentTeam)
            ->latest('start_date')
            ->limit(5)
            ->get()
            ->map(fn (Lease $lease) => [
                'id' => $lease->id,
                'renter_name' => $lease->renter->name,
                'property_name' => $lease->property->name,
                'status' => $lease->computedStatus($today),
                'start_date' => $lease->start_date->toDateString(),
                'monthly_rent_amount' => $lease->monthly_rent_amount,
                'currency' => $lease->currency,
            ]);

        $recentPayments = RentPayment::query()
            ->with(['property', 'renter'])
            ->whereBelongsTo($currentTeam)
            ->latest('payment_date')
            ->limit(5)
            ->get()
            ->map(fn (RentPayment $payment) => [
                'id' => $payment->id,
                'renter_name' => $payment->renter->name,
                'property_name' => $payment->property->name,
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'payment_date' => $payment->payment_date->toDateString(),
                'status' => $payment->status,
            ]);

        $recentExpenses = Expense::query()
            ->with('property')
            ->whereBelongsTo($currentTeam)
            ->latest('expense_date')
            ->limit(5)
            ->get()
            ->map(fn (Expense $expense) => [
                'id' => $expense->id,
                'title' => $expense->title,
                'property_name' => $expense->property->name,
                'amount' => $expense->amount,
                'currency' => $expense->currency,
                'expense_date' => $expense->expense_date->toDateString(),
                'status' => $expense->status,
            ]);

        return Inertia::render('dashboard', [
            'pendingInvitations' => $pendingInvitations,
            'summary' => [
                'property_count' => $propertyCount,
                'active_lease_count' => $activeLeaseCount,
                'estimated_monthly_rent' => $this->decimalString($estimatedMonthlyRent),
                'current_month_payments' => $this->decimalString($currentMonthPayments),
                'current_month_rent_deductions' => $this->decimalString($currentMonthRentDeductions),
                'current_month_covered_rent' => $this->decimalString($currentMonthCoveredRent),
                'remaining_rent' => $this->decimalString($remainingRent),
                'overdue_count' => $overdueLeases->count(),
                'occupancy_label' => "{$occupiedPropertyCount}/{$propertyCount}",
                'occupancy_rate' => $propertyCount > 0
                    ? round(($occupiedPropertyCount / $propertyCount) * 100)
                    : 0,
                'current_month_expenses' => $this->decimalString($ownerSupportedExpenses),
                'current_month_profit' => $this->decimalString($currentMonthCoveredRent - $ownerSupportedExpenses),
                'tenant_reimbursement_expenses' => $this->decimalString($tenantReimbursementExpenses),
                'utility_deduction_expenses' => $this->decimalString($utilityDeductionExpenses),
                'unsettled_tenant_paid_owner_expenses' => $this->decimalString($unsettledTenantPaidOwnerExpenses),
                'recoverable_expenses' => $this->decimalString($recoverableExpenses),
                'currency' => 'RON',
            ],
            'propertyStatusSummary' => [
                'active' => $occupiedPropertyCount,
                'available' => $propertyCount - $occupiedPropertyCount,
            ],
            'overdueLeases' => $overdueLeases,
            'upcomingPayments' => $upcomingPayments,
            'propertiesWithoutActiveLease' => $propertiesWithoutActiveLease,
            'recentLeases' => $recentLeases,
            'recentPayments' => $recentPayments,
            'recentExpenses' => $recentExpenses,
        ]);
    }

    private function decimalString(float|int|string $amount): string
    {
        return number_format((float) $amount, 2, '.', '');
    }
}
