<?php

namespace App\Http\Controllers;

use App\Http\Requests\Leases\SaveLeaseRequest;
use App\Models\Lease;
use App\Models\Property;
use App\Models\Renter;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class LeaseController extends Controller
{
    /**
     * Display a listing of leases for the current workspace.
     */
    public function index(Request $request, Team $currentTeam): Response
    {
        Gate::authorize('viewAny', [Lease::class, $currentTeam]);

        $leases = Lease::query()
            ->with(['property', 'renter'])
            ->whereBelongsTo($currentTeam)
            ->latest('start_date')
            ->latest()
            ->get()
            ->map(fn (Lease $lease) => $this->serializeLease($lease));

        return Inertia::render('leases/index', [
            'leases' => $leases,
            'leaseStatuses' => $this->leaseStatuses(),
        ]);
    }

    /**
     * Show the form for creating a new lease.
     */
    public function create(Team $currentTeam): Response
    {
        Gate::authorize('create', [Lease::class, $currentTeam]);

        return Inertia::render('leases/create', [
            'properties' => $this->propertyOptions($currentTeam),
            'leaseStatuses' => $this->leaseStatuses(),
        ]);
    }

    /**
     * Store a newly created lease.
     */
    public function store(SaveLeaseRequest $request, Team $currentTeam): RedirectResponse
    {
        $validated = $request->validatedWithDefaults();

        DB::transaction(function () use ($currentTeam, $validated) {
            $renter = Renter::create([
                'team_id' => $currentTeam->id,
                'name' => $validated['renter_name'],
                'email' => $validated['renter_email'] ?? null,
                'phone' => $validated['renter_phone'] ?? null,
                'notes' => $validated['renter_notes'] ?? null,
            ]);

            return Lease::create([
                ...$this->leaseAttributes($validated),
                'team_id' => $currentTeam->id,
                'renter_id' => $renter->id,
            ]);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Lease created.')]);

        return to_route('leases.index', ['current_team' => $currentTeam]);
    }

    /**
     * Display the lease details.
     */
    public function show(Team $currentTeam, Lease $lease): Response
    {
        Gate::authorize('view', $lease);
        $this->abortIfLeaseIsOutsideWorkspace($currentTeam, $lease);

        return Inertia::render('leases/show', [
            'lease' => $this->serializeLease($lease->load(['property', 'renter'])),
        ]);
    }

    /**
     * Show the form for editing the lease.
     */
    public function edit(Team $currentTeam, Lease $lease): Response
    {
        Gate::authorize('update', $lease);
        $this->abortIfLeaseIsOutsideWorkspace($currentTeam, $lease);

        return Inertia::render('leases/edit', [
            'lease' => $this->serializeLease($lease->load(['property', 'renter'])),
            'properties' => $this->propertyOptions($currentTeam),
            'leaseStatuses' => $this->leaseStatuses(),
        ]);
    }

    /**
     * Update the lease and related renter contact.
     */
    public function update(SaveLeaseRequest $request, Team $currentTeam, Lease $lease): RedirectResponse
    {
        $this->abortIfLeaseIsOutsideWorkspace($currentTeam, $lease);

        $validated = $request->validatedWithDefaults();

        DB::transaction(function () use ($lease, $validated) {
            $lease->renter->update([
                'name' => $validated['renter_name'],
                'email' => $validated['renter_email'] ?? null,
                'phone' => $validated['renter_phone'] ?? null,
                'notes' => $validated['renter_notes'] ?? null,
            ]);

            $lease->update($this->leaseAttributes($validated));
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Lease updated.')]);

        return to_route('leases.show', [
            'current_team' => $currentTeam,
            'lease' => $lease,
        ]);
    }

    /**
     * Remove the lease.
     */
    public function destroy(Team $currentTeam, Lease $lease): RedirectResponse
    {
        Gate::authorize('delete', $lease);
        $this->abortIfLeaseIsOutsideWorkspace($currentTeam, $lease);

        $lease->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Lease deleted.')]);

        return to_route('leases.index', ['current_team' => $currentTeam]);
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function leaseStatuses(): array
    {
        return [
            ['value' => Lease::STATUS_UPCOMING, 'label' => 'Viitor'],
            ['value' => Lease::STATUS_ACTIVE, 'label' => 'Activ'],
            ['value' => Lease::STATUS_ENDED, 'label' => 'Închis'],
        ];
    }

    /**
     * @return array<int, array{id: int, name: string, address_line: string, city: string, monthly_rent_amount: string|null, currency: string}>
     */
    private function propertyOptions(Team $currentTeam): array
    {
        return Property::query()
            ->whereBelongsTo($currentTeam)
            ->orderBy('name')
            ->get(['id', 'name', 'address_line', 'city', 'monthly_rent_amount', 'currency'])
            ->map(fn (Property $property) => [
                'id' => $property->id,
                'name' => $property->name,
                'address_line' => $property->address_line,
                'city' => $property->city,
                'monthly_rent_amount' => $property->monthly_rent_amount,
                'currency' => $property->currency,
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeLease(Lease $lease): array
    {
        return [
            'id' => $lease->id,
            'team_id' => $lease->team_id,
            'property_id' => $lease->property_id,
            'renter_id' => $lease->renter_id,
            'start_date' => $lease->start_date->toDateString(),
            'end_date' => $lease->end_date?->toDateString(),
            'monthly_rent_amount' => $lease->monthly_rent_amount,
            'currency' => $lease->currency,
            'rent_due_day' => $lease->rent_due_day,
            'deposit_amount' => $lease->deposit_amount,
            'status' => $lease->computedStatus(),
            'notes' => $lease->notes,
            'property' => [
                'id' => $lease->property->id,
                'name' => $lease->property->name,
                'address_line' => $lease->property->address_line,
                'city' => $lease->property->city,
                'monthly_rent_amount' => $lease->property->monthly_rent_amount,
                'currency' => $lease->property->currency,
            ],
            'renter' => [
                'id' => $lease->renter->id,
                'name' => $lease->renter->name,
                'email' => $lease->renter->email,
                'phone' => $lease->renter->phone,
                'notes' => $lease->renter->notes,
            ],
            'created_at' => $lease->created_at?->toISOString(),
            'updated_at' => $lease->updated_at?->toISOString(),
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function leaseAttributes(array $validated): array
    {
        /** @var Property $property */
        $property = Property::query()->findOrFail((int) $validated['property_id']);

        return [
            'property_id' => $validated['property_id'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'] ?? null,
            'monthly_rent_amount' => $property->monthly_rent_amount,
            'currency' => $validated['currency'],
            'rent_due_day' => $validated['rent_due_day'],
            'deposit_amount' => $validated['deposit_amount'] ?? null,
            'status' => Lease::computeStatusForDates(
                $validated['start_date'],
                $validated['end_date'] ?? null,
            ),
            'notes' => $validated['notes'] ?? null,
        ];
    }

    private function abortIfLeaseIsOutsideWorkspace(Team $currentTeam, Lease $lease): void
    {
        abort_unless($lease->team_id === $currentTeam->id, 404);
    }
}
