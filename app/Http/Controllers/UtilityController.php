<?php

namespace App\Http\Controllers;

use App\Models\Lease;
use App\Models\Property;
use App\Models\Team;
use App\Models\UtilityAccount;
use App\Models\UtilityBill;
use App\Support\MoneyInput;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class UtilityController extends Controller
{
    public function index(Request $request, Team $currentTeam): Response
    {
        Gate::authorize('viewAny', [UtilityAccount::class, $currentTeam]);
        Gate::authorize('viewAny', [UtilityBill::class, $currentTeam]);

        $today = Carbon::today(config('app.timezone'));

        $accounts = UtilityAccount::query()
            ->whereBelongsTo($currentTeam)
            ->with([
                'property:id,team_id,name,city',
                'lease.renter:id,team_id,name',
            ])
            ->withCount('bills')
            ->orderBy('provider_name')
            ->orderBy('id')
            ->get()
            ->map(fn (UtilityAccount $account): array => [
                'id' => $account->id,
                'property_id' => $account->property_id,
                'lease_id' => $account->lease_id,
                'provider_name' => $account->provider_name,
                'service_type' => $account->service_type->value,
                'account_identifier' => $account->account_identifier,
                'responsible_party' => $account->responsible_party->value,
                'status' => $account->status->value,
                'notes' => $account->notes,
                'bill_count' => $account->bills_count,
                'property' => [
                    'id' => $account->property->id,
                    'name' => $account->property->name,
                    'city' => $account->property->city,
                ],
                'renter_name' => $account->lease?->renter?->name,
            ]);

        $bills = UtilityBill::query()
            ->whereBelongsTo($currentTeam)
            ->with([
                'utilityAccount:id,team_id,provider_name,service_type,responsible_party',
                'property:id,team_id,name,city',
                'lease.renter:id,team_id,name',
                'document:id,team_id,original_name',
            ])
            ->orderByDesc('due_date')
            ->orderByDesc('id')
            ->get()
            ->map(fn (UtilityBill $bill): array => [
                'id' => $bill->id,
                'utility_account_id' => $bill->utility_account_id,
                'invoice_number' => $bill->invoice_number,
                'billing_period_start' => $bill->billing_period_start->toDateString(),
                'billing_period_end' => $bill->billing_period_end->toDateString(),
                'issue_date' => $bill->issue_date->toDateString(),
                'due_date' => $bill->due_date->toDateString(),
                'amount' => MoneyInput::fromMinorUnits($bill->amount_minor),
                'amount_minor' => $bill->amount_minor,
                'currency' => $bill->currency,
                'status' => $bill->status->value,
                'responsible_party' => $bill->responsible_party?->value
                    ?? $bill->utilityAccount->responsible_party->value,
                'paid_by' => $bill->paid_by?->value,
                'paid_on' => $bill->paid_on?->toDateString(),
                'notes' => $bill->notes,
                'overdue' => $bill->status->value === 'unpaid'
                    && $bill->due_date->startOfDay()->lessThan($today),
                'account' => [
                    'provider_name' => $bill->utilityAccount->provider_name,
                    'service_type' => $bill->utilityAccount->service_type->value,
                ],
                'property' => [
                    'id' => $bill->property->id,
                    'name' => $bill->property->name,
                    'city' => $bill->property->city,
                ],
                'renter_name' => $bill->lease?->renter?->name,
                'document' => $bill->document ? [
                    'id' => $bill->document->id,
                    'original_name' => $bill->document->original_name,
                ] : null,
            ]);

        return Inertia::render('utilities/index', [
            'accounts' => $accounts,
            'bills' => $bills,
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
                'active_accounts' => $accounts->where('status', 'active')->count(),
                'unpaid_bills' => $bills->where('status', 'unpaid')->count(),
                'overdue_bills' => $bills->where('overdue', true)->count(),
                'attached_bills' => $bills->whereNotNull('document')->count(),
            ],
        ]);
    }
}
