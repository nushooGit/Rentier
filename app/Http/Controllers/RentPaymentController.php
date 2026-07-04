<?php

namespace App\Http\Controllers;

use App\Http\Requests\RentPayments\SaveRentPaymentRequest;
use App\Models\Lease;
use App\Models\RentPayment;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class RentPaymentController extends Controller
{
    /**
     * Display a listing of rent payments for the current workspace.
     */
    public function index(Request $request, Team $currentTeam): Response
    {
        Gate::authorize('viewAny', [RentPayment::class, $currentTeam]);

        $payments = RentPayment::query()
            ->with(['lease', 'property', 'renter'])
            ->whereBelongsTo($currentTeam)
            ->latest('payment_date')
            ->latest()
            ->get()
            ->map(fn (RentPayment $payment) => $this->serializePayment($payment));

        return Inertia::render('payments/index', [
            'payments' => $payments,
            'paymentMethods' => $this->paymentMethods(),
            'paymentStatuses' => $this->paymentStatuses(),
        ]);
    }

    /**
     * Show the form for creating a new payment.
     */
    public function create(Team $currentTeam): Response
    {
        Gate::authorize('create', [RentPayment::class, $currentTeam]);

        return Inertia::render('payments/create', [
            'leases' => $this->leaseOptions($currentTeam),
            'paymentMethods' => $this->paymentMethods(),
            'paymentStatuses' => $this->paymentStatuses(),
        ]);
    }

    /**
     * Store a newly created payment.
     */
    public function store(SaveRentPaymentRequest $request, Team $currentTeam): RedirectResponse
    {
        $validated = $request->validatedWithDefaults();
        $lease = $this->findWorkspaceLease($currentTeam, $validated['lease_id']);

        $payment = RentPayment::create([
            ...$this->paymentAttributes($validated),
            'team_id' => $currentTeam->id,
            'property_id' => $lease->property_id,
            'renter_id' => $lease->renter_id,
        ]);
        $payment->update(['status' => $this->persistedStatusKey($this->statusSummary($payment)['status_key'])]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Payment created.')]);

        return to_route('payments.index', ['current_team' => $currentTeam]);
    }

    /**
     * Display the payment details.
     */
    public function show(Team $currentTeam, RentPayment $payment): Response
    {
        Gate::authorize('view', $payment);
        $this->abortIfPaymentIsOutsideWorkspace($currentTeam, $payment);

        return Inertia::render('payments/show', [
            'payment' => $this->serializePayment($payment->load(['lease', 'property', 'renter'])),
        ]);
    }

    /**
     * Show the form for editing the payment.
     */
    public function edit(Team $currentTeam, RentPayment $payment): Response
    {
        Gate::authorize('update', $payment);
        $this->abortIfPaymentIsOutsideWorkspace($currentTeam, $payment);

        return Inertia::render('payments/edit', [
            'payment' => $this->serializePayment($payment->load(['lease', 'property', 'renter'])),
            'leases' => $this->leaseOptions($currentTeam),
            'paymentMethods' => $this->paymentMethods(),
            'paymentStatuses' => $this->paymentStatuses(),
        ]);
    }

    /**
     * Update the payment.
     */
    public function update(SaveRentPaymentRequest $request, Team $currentTeam, RentPayment $payment): RedirectResponse
    {
        $this->abortIfPaymentIsOutsideWorkspace($currentTeam, $payment);

        $validated = $request->validatedWithDefaults();
        $lease = $this->findWorkspaceLease($currentTeam, $validated['lease_id']);

        $payment->update([
            ...$this->paymentAttributes($validated),
            'property_id' => $lease->property_id,
            'renter_id' => $lease->renter_id,
        ]);
        $payment->update(['status' => $this->persistedStatusKey($this->statusSummary($payment->refresh())['status_key'])]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Payment updated.')]);

        return to_route('payments.show', [
            'current_team' => $currentTeam,
            'payment' => $payment,
        ]);
    }

    /**
     * Remove the payment.
     */
    public function destroy(Team $currentTeam, RentPayment $payment): RedirectResponse
    {
        Gate::authorize('delete', $payment);
        $this->abortIfPaymentIsOutsideWorkspace($currentTeam, $payment);

        $payment->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Payment deleted.')]);

        return to_route('payments.index', ['current_team' => $currentTeam]);
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function paymentMethods(): array
    {
        return [
            ['value' => 'cash', 'label' => 'Cash'],
            ['value' => 'bank_transfer', 'label' => 'Bank transfer'],
            ['value' => 'card', 'label' => 'Card'],
            ['value' => 'other', 'label' => 'Other'],
        ];
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function paymentStatuses(): array
    {
        return [
            ['value' => 'paid', 'label' => 'Achitată integral'],
            ['value' => 'partial', 'label' => 'Parțial achitată'],
            ['value' => 'pending', 'label' => 'În așteptare'],
            ['value' => 'cancelled', 'label' => 'Anulată'],
        ];
    }

    /**
     * @return array<int, array{id: int, label: string, property: string, renter: string, monthly_rent_amount: string, deposit_amount: string|null, currency: string}>
     */
    private function leaseOptions(Team $currentTeam): array
    {
        return Lease::query()
            ->with(['property', 'renter'])
            ->whereBelongsTo($currentTeam)
            ->latest('start_date')
            ->get()
            ->map(fn (Lease $lease) => [
                'id' => $lease->id,
                'label' => $lease->renter->name.' - '.$lease->property->name,
                'property' => $lease->property->name,
                'renter' => $lease->renter->name,
                'monthly_rent_amount' => $lease->monthly_rent_amount,
                'deposit_amount' => $lease->deposit_amount,
                'currency' => $lease->currency,
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function serializePayment(RentPayment $payment): array
    {
        $paymentType = $payment->payment_type ?? 'rent';
        $statusSummary = $this->statusSummary($payment);
        $guaranteeSummary = $paymentType === 'guarantee' ? $statusSummary : null;

        return [
            'id' => $payment->id,
            'team_id' => $payment->team_id,
            'lease_id' => $payment->lease_id,
            'property_id' => $payment->property_id,
            'renter_id' => $payment->renter_id,
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'payment_type' => $paymentType,
            'payment_date' => $payment->payment_date->toDateString(),
            'period_month' => $payment->period_month,
            'period_year' => $payment->period_year,
            'method' => $payment->method,
            'status' => $payment->status,
            'status_summary' => $statusSummary,
            'notes' => $payment->notes,
            'guarantee_summary' => $guaranteeSummary,
            'lease' => [
                'id' => $payment->lease->id,
                'status' => $payment->lease->computedStatus(),
                'start_date' => $payment->lease->start_date->toDateString(),
                'end_date' => $payment->lease->end_date?->toDateString(),
            ],
            'property' => [
                'id' => $payment->property->id,
                'name' => $payment->property->name,
                'city' => $payment->property->city,
            ],
            'renter' => [
                'id' => $payment->renter->id,
                'name' => $payment->renter->name,
            ],
            'created_at' => $payment->created_at?->toISOString(),
            'updated_at' => $payment->updated_at?->toISOString(),
        ];
    }

    /**
     * @return array{expected_amount: string, collected_amount: string, remaining_amount: string, status_key: string, status_label: string}
     */
    private function statusSummary(RentPayment $payment): array
    {
        return ($payment->payment_type ?? 'rent') === 'guarantee'
            ? $this->guaranteeSummary($payment)
            : $this->rentSummary($payment);
    }

    /**
     * @return array{expected_amount: string, collected_amount: string, remaining_amount: string, status_key: string, status_label: string}
     */
    private function rentSummary(RentPayment $payment): array
    {
        $expectedAmount = (float) $payment->lease->monthly_rent_amount;
        $collectedAmount = (float) RentPayment::query()
            ->where('lease_id', $payment->lease_id)
            ->where(function ($query) {
                $query
                    ->where('payment_type', 'rent')
                    ->orWhereNull('payment_type');
            })
            ->where('period_month', $payment->period_month)
            ->where('period_year', $payment->period_year)
            ->sum('amount');
        $remainingAmount = max($expectedAmount - $collectedAmount, 0);

        if ($expectedAmount > 0 && $collectedAmount >= $expectedAmount) {
            $statusKey = 'paid';
            $statusLabel = 'Chirie achitată integral';
        } elseif ($collectedAmount > 0) {
            $statusKey = 'partial';
            $statusLabel = 'Chirie parțial achitată';
        } else {
            $statusKey = 'pending';
            $statusLabel = 'Chirie neîncasată';
        }

        return [
            'expected_amount' => $this->decimalString($expectedAmount),
            'collected_amount' => $this->decimalString($collectedAmount),
            'remaining_amount' => $this->decimalString($remainingAmount),
            'status_key' => $statusKey,
            'status_label' => $statusLabel,
        ];
    }

    /**
     * @return array{expected_amount: string, collected_amount: string, remaining_amount: string, status_key: string, status_label: string}
     */
    private function guaranteeSummary(RentPayment $payment): array
    {
        $expectedAmount = (float) ($payment->lease->deposit_amount ?? 0);
        $collectedAmount = (float) RentPayment::query()
            ->where('lease_id', $payment->lease_id)
            ->where('payment_type', 'guarantee')
            ->sum('amount');
        $remainingAmount = max($expectedAmount - $collectedAmount, 0);

        if ($expectedAmount <= 0) {
            $statusKey = 'not_configured';
            $statusLabel = 'Garanție nesetată';
        } elseif ($collectedAmount >= $expectedAmount) {
            $statusKey = 'paid';
            $statusLabel = 'Garanție achitată integral';
        } elseif ($collectedAmount > 0) {
            $statusKey = 'partial';
            $statusLabel = 'Garanție parțial achitată';
        } else {
            $statusKey = 'unpaid';
            $statusLabel = 'Garanție neîncasată';
        }

        return [
            'expected_amount' => $this->decimalString($expectedAmount),
            'collected_amount' => $this->decimalString($collectedAmount),
            'remaining_amount' => $this->decimalString($remainingAmount),
            'status_key' => $statusKey,
            'status_label' => $statusLabel,
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function paymentAttributes(array $validated): array
    {
        return [
            'lease_id' => $validated['lease_id'],
            'amount' => $validated['amount'],
            'currency' => $validated['currency'],
            'payment_type' => $validated['payment_type'],
            'payment_date' => $validated['payment_date'],
            'period_month' => $validated['payment_type'] === 'rent' ? $validated['period_month'] : null,
            'period_year' => $validated['payment_type'] === 'rent' ? $validated['period_year'] : null,
            'method' => $validated['method'] ?? null,
            'status' => 'pending',
            'notes' => $validated['notes'] ?? null,
        ];
    }

    private function abortIfPaymentIsOutsideWorkspace(Team $currentTeam, RentPayment $payment): void
    {
        abort_unless($payment->team_id === $currentTeam->id, 404);
    }

    private function findWorkspaceLease(Team $currentTeam, mixed $leaseId): Lease
    {
        return Lease::query()
            ->whereBelongsTo($currentTeam)
            ->whereKey($leaseId)
            ->firstOrFail();
    }

    private function decimalString(float|int|string $amount): string
    {
        return number_format((float) $amount, 2, '.', '');
    }

    private function persistedStatusKey(string $statusKey): string
    {
        return in_array($statusKey, ['paid', 'partial'], true) ? $statusKey : 'pending';
    }
}
