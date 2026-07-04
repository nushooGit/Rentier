<?php

use App\Enums\TeamRole;
use App\Models\Lease;
use App\Models\Property;
use App\Models\RentPayment;
use App\Models\Team;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

function validPropertyPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Central Apartment',
        'type' => 'apartment',
        'country' => 'Romania',
        'city' => 'Bucharest',
        'county_or_sector' => 'Sector 1',
        'address_line' => 'Strada Exemplu 10',
        'postal_code' => '010101',
        'rooms' => 2,
        'usable_area_sqm' => 58.5,
        'floor' => 3,
        'total_floors' => 8,
        'status' => 'available',
        'monthly_rent_amount' => 2500,
        'currency' => 'RON',
        'rent_due_day' => 5,
        'deposit_amount' => 2500,
        'notes' => 'Near metro.',
    ], $overrides);
}

test('properties index shows properties for the current workspace', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $otherTeam = Team::factory()->create();

    Property::factory()->for($team)->create(['name' => 'Visible Property']);
    Property::factory()->for($otherTeam)->create(['name' => 'Hidden Property']);

    $response = $this
        ->actingAs($user)
        ->get(route('properties.index', $team));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('properties/index')
            ->has('properties', 1)
            ->where('properties.0.name', 'Visible Property')
        );
});

test('workspace members can create properties', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $response = $this
        ->actingAs($user)
        ->post(route('properties.store', $team), validPropertyPayload());

    $property = Property::first();

    $response->assertRedirect(route('properties.index', $team));

    $this->assertDatabaseHas('properties', [
        'team_id' => $team->id,
        'name' => 'Central Apartment',
        'city' => 'Bucharest',
        'address_line' => 'Strada Exemplu 10',
    ]);
});

test('property validation requires core fields', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $response = $this
        ->actingAs($user)
        ->post(route('properties.store', $team), validPropertyPayload([
            'name' => '',
            'city' => '',
            'address_line' => '',
            'monthly_rent_amount' => -1,
        ]));

    $response->assertSessionHasErrors([
        'name',
        'city',
        'address_line',
        'monthly_rent_amount',
    ]);
});

test('property without active lease shows available occupancy status', function () {
    Carbon::setTestNow('2026-07-15');

    $user = User::factory()->create();
    $team = $user->currentTeam;
    $property = Property::factory()->for($team)->create([
        'status' => 'occupied',
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('properties.show', [$team, $property]));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('properties/show')
            ->where('property.status', 'available')
        );

    Carbon::setTestNow();
});

test('property with active current lease shows occupied occupancy status', function () {
    Carbon::setTestNow('2026-07-15');

    $user = User::factory()->create();
    $team = $user->currentTeam;
    $property = Property::factory()->for($team)->create([
        'status' => 'available',
    ]);

    Lease::factory()->for($team)->create([
        'property_id' => $property->id,
        'start_date' => '2026-07-01',
        'end_date' => '2027-07-01',
        'status' => 'ended',
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('properties.index', $team));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('properties/index')
            ->where('properties.0.status', 'occupied')
        );

    Carbon::setTestNow();
});

test('property card shows paid rent status for the current month', function () {
    Carbon::setTestNow('2026-07-15');

    $user = User::factory()->create();
    $team = $user->currentTeam;
    $property = Property::factory()->for($team)->create();
    $lease = Lease::factory()->for($team)->create([
        'property_id' => $property->id,
        'start_date' => '2026-07-01',
        'end_date' => '2027-07-01',
        'monthly_rent_amount' => 2500,
        'rent_due_day' => 5,
    ]);

    RentPayment::factory()->for($team)->create([
        'lease_id' => $lease->id,
        'property_id' => $property->id,
        'renter_id' => $lease->renter_id,
        'amount' => 2500,
        'period_month' => 7,
        'period_year' => 2026,
    ]);

    $this
        ->actingAs($user)
        ->get(route('properties.index', $team))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('properties.0.rent_payment_status.key', 'paid')
            ->where('properties.0.rent_payment_status.label', 'Plătită luna asta')
        );

    Carbon::setTestNow();
});

test('property card shows partial rent status for the current month', function () {
    Carbon::setTestNow('2026-07-15');

    $user = User::factory()->create();
    $team = $user->currentTeam;
    $property = Property::factory()->for($team)->create();
    $lease = Lease::factory()->for($team)->create([
        'property_id' => $property->id,
        'start_date' => '2026-07-01',
        'end_date' => '2027-07-01',
        'monthly_rent_amount' => 2500,
        'rent_due_day' => 5,
    ]);

    RentPayment::factory()->for($team)->create([
        'lease_id' => $lease->id,
        'property_id' => $property->id,
        'renter_id' => $lease->renter_id,
        'amount' => 1000,
        'period_month' => 7,
        'period_year' => 2026,
    ]);

    $this
        ->actingAs($user)
        ->get(route('properties.index', $team))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('properties.0.rent_payment_status.key', 'partial')
            ->where('properties.0.rent_payment_status.label', 'Plătită parțial')
        );

    Carbon::setTestNow();
});

test('property card shows upcoming rent status before due date', function () {
    Carbon::setTestNow('2026-07-03');

    $user = User::factory()->create();
    $team = $user->currentTeam;
    $property = Property::factory()->for($team)->create();

    Lease::factory()->for($team)->create([
        'property_id' => $property->id,
        'start_date' => '2026-07-01',
        'end_date' => '2027-07-01',
        'monthly_rent_amount' => 2500,
        'rent_due_day' => 5,
    ]);

    $this
        ->actingAs($user)
        ->get(route('properties.index', $team))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('properties.0.rent_payment_status.key', 'upcoming')
            ->where('properties.0.rent_payment_status.label', 'Mai sunt 2 zile până la plată')
            ->where('properties.0.rent_payment_status.days', 2)
            ->where('properties.0.rent_payment_status.due_date', '2026-07-05')
        );

    Carbon::setTestNow();
});

test('property card shows due today rent status on due date', function () {
    Carbon::setTestNow('2026-07-05');

    $user = User::factory()->create();
    $team = $user->currentTeam;
    $property = Property::factory()->for($team)->create();

    Lease::factory()->for($team)->create([
        'property_id' => $property->id,
        'start_date' => '2026-07-01',
        'end_date' => '2027-07-01',
        'monthly_rent_amount' => 2500,
        'rent_due_day' => 5,
    ]);

    $this
        ->actingAs($user)
        ->get(route('properties.index', $team))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('properties.0.rent_payment_status.key', 'due_today')
            ->where('properties.0.rent_payment_status.label', 'Scadentă azi')
            ->where('properties.0.rent_payment_status.days', 0)
        );

    Carbon::setTestNow();
});

test('property card shows overdue rent status after due date', function () {
    Carbon::setTestNow('2026-07-08');

    $user = User::factory()->create();
    $team = $user->currentTeam;
    $property = Property::factory()->for($team)->create();

    Lease::factory()->for($team)->create([
        'property_id' => $property->id,
        'start_date' => '2026-07-01',
        'end_date' => '2027-07-01',
        'monthly_rent_amount' => 2500,
        'rent_due_day' => 5,
    ]);

    $this
        ->actingAs($user)
        ->get(route('properties.index', $team))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('properties.0.rent_payment_status.key', 'overdue')
            ->where('properties.0.rent_payment_status.label', 'Întârziată cu 3 zile')
            ->where('properties.0.rent_payment_status.days', 3)
        );

    Carbon::setTestNow();
});

test('property card uses last valid day when rent due day is missing from month', function () {
    Carbon::setTestNow('2027-02-28');

    $user = User::factory()->create();
    $team = $user->currentTeam;
    $property = Property::factory()->for($team)->create();

    Lease::factory()->for($team)->create([
        'property_id' => $property->id,
        'start_date' => '2027-01-01',
        'end_date' => '2028-01-01',
        'monthly_rent_amount' => 2500,
        'rent_due_day' => 31,
    ]);

    $this
        ->actingAs($user)
        ->get(route('properties.index', $team))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('properties.0.rent_payment_status.key', 'due_today')
            ->where('properties.0.rent_payment_status.due_date', '2027-02-28')
        );

    Carbon::setTestNow();
});

test('property with future lease still shows available occupancy status', function () {
    Carbon::setTestNow('2026-07-15');

    $user = User::factory()->create();
    $team = $user->currentTeam;
    $property = Property::factory()->for($team)->create();

    Lease::factory()->for($team)->create([
        'property_id' => $property->id,
        'start_date' => '2026-08-01',
        'end_date' => '2027-07-31',
        'status' => 'active',
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('properties.show', [$team, $property]));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('properties/show')
            ->where('property.status', 'available')
        );

    Carbon::setTestNow();
});

test('property with expired lease shows available occupancy status', function () {
    Carbon::setTestNow('2026-07-15');

    $user = User::factory()->create();
    $team = $user->currentTeam;
    $property = Property::factory()->for($team)->create();

    Lease::factory()->for($team)->create([
        'property_id' => $property->id,
        'start_date' => '2025-07-01',
        'end_date' => '2026-06-30',
        'status' => 'active',
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('properties.show', [$team, $property]));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('properties/show')
            ->where('property.status', 'available')
        );

    Carbon::setTestNow();
});

test('workspace members can view and update properties regardless of team role', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $team = Team::factory()->create();

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
    $team->members()->attach($member, ['role' => TeamRole::Member->value]);

    $property = Property::factory()->for($team)->create([
        'name' => 'Original Property',
    ]);

    $this
        ->actingAs($member)
        ->get(route('properties.show', [$team, $property]))
        ->assertOk();

    $response = $this
        ->actingAs($member)
        ->patch(route('properties.update', [$team, $property]), validPropertyPayload([
            'name' => 'Updated Property',
            'status' => 'occupied',
        ]));

    $response->assertRedirect(route('properties.show', [$team, $property]));

    $this->assertDatabaseHas('properties', [
        'id' => $property->id,
        'name' => 'Updated Property',
        'status' => 'occupied',
    ]);
});

test('users cannot access a workspace they do not belong to', function () {
    $user = User::factory()->create();
    $otherTeam = Team::factory()->create();

    $this->withoutExceptionHandling();

    expect(fn () => $this
        ->actingAs($user)
        ->get(route('properties.index', $otherTeam)))
        ->toThrow(HttpException::class);
});

test('users cannot access properties owned by another workspace', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $otherTeam = Team::factory()->create();
    $property = Property::factory()->for($otherTeam)->create();

    $this->withoutExceptionHandling();

    expect(fn () => $this
        ->actingAs($user)
        ->get(route('properties.show', [$team, $property])))
        ->toThrow(AuthorizationException::class);
});

test('workspace members can delete properties', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $property = Property::factory()->for($team)->create();

    $response = $this
        ->actingAs($user)
        ->delete(route('properties.destroy', [$team, $property]));

    $response->assertRedirect(route('properties.index', $team));

    $this->assertDatabaseMissing('properties', [
        'id' => $property->id,
    ]);
});
