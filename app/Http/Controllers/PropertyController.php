<?php

namespace App\Http\Controllers;

use App\Http\Requests\Properties\SavePropertyRequest;
use App\Models\Property;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PropertyController extends Controller
{
    /**
     * Display a listing of properties for the current workspace.
     */
    public function index(Request $request, Team $currentTeam): Response
    {
        Gate::authorize('viewAny', [Property::class, $currentTeam]);

        $properties = Property::query()
            ->whereBelongsTo($currentTeam)
            ->latest()
            ->get()
            ->map(fn (Property $property) => $this->serializeProperty($property));

        return Inertia::render('properties/index', [
            'properties' => $properties,
            'propertyTypes' => $this->propertyTypes(),
            'propertyStatuses' => $this->propertyStatuses(),
        ]);
    }

    /**
     * Show the form for creating a new property.
     */
    public function create(Team $currentTeam): Response
    {
        Gate::authorize('create', [Property::class, $currentTeam]);

        return Inertia::render('properties/create', [
            'propertyTypes' => $this->propertyTypes(),
            'propertyStatuses' => $this->propertyStatuses(),
        ]);
    }

    /**
     * Store a newly created property.
     */
    public function store(SavePropertyRequest $request, Team $currentTeam): RedirectResponse
    {
        Property::create([
            ...$request->validatedWithDefaults(),
            'team_id' => $currentTeam->id,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Property created.')]);

        return to_route('properties.index', ['current_team' => $currentTeam]);
    }

    /**
     * Display the property details.
     */
    public function show(Team $currentTeam, Property $property): Response
    {
        Gate::authorize('view', $property);
        $this->abortIfPropertyIsOutsideWorkspace($currentTeam, $property);

        return Inertia::render('properties/show', [
            'property' => $this->serializeProperty($property),
        ]);
    }

    /**
     * Show the form for editing the property.
     */
    public function edit(Team $currentTeam, Property $property): Response
    {
        Gate::authorize('update', $property);
        $this->abortIfPropertyIsOutsideWorkspace($currentTeam, $property);

        return Inertia::render('properties/edit', [
            'property' => $this->serializeProperty($property),
            'propertyTypes' => $this->propertyTypes(),
            'propertyStatuses' => $this->propertyStatuses(),
        ]);
    }

    /**
     * Update the property.
     */
    public function update(SavePropertyRequest $request, Team $currentTeam, Property $property): RedirectResponse
    {
        $this->abortIfPropertyIsOutsideWorkspace($currentTeam, $property);

        $property->update($request->validatedWithDefaults());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Property updated.')]);

        return to_route('properties.show', [
            'current_team' => $currentTeam,
            'property' => $property,
        ]);
    }

    /**
     * Remove the property.
     */
    public function destroy(Team $currentTeam, Property $property): RedirectResponse
    {
        Gate::authorize('delete', $property);
        $this->abortIfPropertyIsOutsideWorkspace($currentTeam, $property);

        $property->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Property deleted.')]);

        return to_route('properties.index', ['current_team' => $currentTeam]);
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function propertyTypes(): array
    {
        return [
            ['value' => 'studio', 'label' => 'Studio'],
            ['value' => 'apartment', 'label' => 'Apartment'],
            ['value' => 'house', 'label' => 'House'],
            ['value' => 'commercial_space', 'label' => 'Commercial space'],
            ['value' => 'office', 'label' => 'Office'],
            ['value' => 'other', 'label' => 'Other'],
        ];
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function propertyStatuses(): array
    {
        return [
            ['value' => 'available', 'label' => 'Available'],
            ['value' => 'occupied', 'label' => 'Occupied'],
            ['value' => 'renovation', 'label' => 'Renovation'],
            ['value' => 'inactive', 'label' => 'Inactive'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeProperty(Property $property): array
    {
        return [
            'id' => $property->id,
            'team_id' => $property->team_id,
            'name' => $property->name,
            'type' => $property->type,
            'country' => $property->country,
            'city' => $property->city,
            'county_or_sector' => $property->county_or_sector,
            'address_line' => $property->address_line,
            'postal_code' => $property->postal_code,
            'rooms' => $property->rooms,
            'usable_area_sqm' => $property->usable_area_sqm,
            'floor' => $property->floor,
            'total_floors' => $property->total_floors,
            'status' => $property->occupancyStatus(),
            'monthly_rent_amount' => $property->monthly_rent_amount,
            'currency' => $property->currency,
            'rent_due_day' => $property->rent_due_day,
            'deposit_amount' => $property->deposit_amount,
            'notes' => $property->notes,
            'created_at' => $property->created_at?->toISOString(),
            'updated_at' => $property->updated_at?->toISOString(),
        ];
    }

    private function abortIfPropertyIsOutsideWorkspace(Team $currentTeam, Property $property): void
    {
        abort_unless($property->team_id === $currentTeam->id, 404);
    }
}
