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

        RentPayment::create([
            ...$this->paymentAttributes($validated),
            'team_id' => $currentTeam->id,
            'property_id' => $lease->property_id,
            'renter_id' => $lease->renter_id,
        ]);

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
     * @return array<int, array{id: int, label: string, property: string, renter: string, monthly_rent_amount: string, currency: string}>
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
                'label' => $lease->renter->name.' · '.$lease->property->name,
                'property' => $lease->property->name,
                'renter' => $lease->renter->name,
                'monthly_rent_amount' => $lease->monthly_rent_amount,
                'currency' => $lease->currency,
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function serializePayment(RentPayment $payment): array
    {
        return [
            'id' => $payment->id,
            'team_id' => $payment->team_id,
            'lease_id' => $payment->lease_id,
            'property_id' => $payment->property_id,
            'renter_id' => $payment->renter_id,
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'payment_date' => $payment->payment_date->toDateString(),
            'period_month' => $payment->period_month,
            'period_year' => $payment->period_year,
            'method' => $payment->method,
            'status' => $payment->status,
            'notes' => $payment->notes,
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
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function paymentAttributes(array $validated): array
    {
        return [
            'lease_id' => $validated['lease_id'],
            'amount' => $validated['amount'],
            'currency' => $validated['currency'],
            'payment_date' => $validated['payment_date'],
            'period_month' => $validated['period_month'],
            'period_year' => $validated['period_year'],
            'method' => $validated['method'] ?? null,
            'status' => $validated['status'],
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
}
