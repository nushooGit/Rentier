<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Lease;
use App\Models\Property;
use App\Models\RentPayment;
use App\Models\Team;
use App\Models\TeamInvitation;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, Team $currentTeam): Response
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

        $activeLeaseCount = Lease::query()
            ->whereBelongsTo($currentTeam)
            ->whereDate('start_date', '<=', $today)
            ->where(function ($query) use ($today) {
                $query
                    ->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $today);
            })
            ->count();

        $estimatedMonthlyRent = Lease::query()
            ->whereBelongsTo($currentTeam)
            ->whereDate('start_date', '<=', $today)
            ->where(function ($query) use ($today) {
                $query
                    ->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $today);
            })
            ->sum('monthly_rent_amount');

        $currentMonthPayments = RentPayment::query()
            ->whereBelongsTo($currentTeam)
            ->whereIn('status', ['paid', 'partial'])
            ->where('period_month', $currentMonth)
            ->where('period_year', $currentYear)
            ->sum('amount');

        $currentMonthExpenses = Expense::query()
            ->whereBelongsTo($currentTeam)
            ->where('status', '!=', 'cancelled')
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
                'estimated_monthly_rent' => (string) $estimatedMonthlyRent,
                'current_month_payments' => (string) $currentMonthPayments,
                'current_month_expenses' => (string) $currentMonthExpenses,
                'current_month_profit' => (string) ($currentMonthPayments - $currentMonthExpenses),
                'currency' => 'RON',
            ],
            'propertyStatusSummary' => [
                'active' => $occupiedPropertyCount,
                'available' => $propertyCount - $occupiedPropertyCount,
            ],
            'recentLeases' => $recentLeases,
            'recentPayments' => $recentPayments,
            'recentExpenses' => $recentExpenses,
        ]);
    }
}
