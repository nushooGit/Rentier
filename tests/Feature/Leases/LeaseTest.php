<?php

use App\Enums\TeamRole;
use App\Models\Lease;
use App\Models\Property;
use App\Models\Renter;
use App\Models\Team;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

function validLeasePayload(Property $property, array $overrides = []): array
{
    return array_merge([
        'property_id' => $property->id,
        'renter_name' => 'Ana Popescu',
        'renter_email' => 'ana@example.com',
        'renter_phone' => '0712345678',
        'renter_notes' => 'Preferă contact prin email.',
        'start_date' => '2026-07-01',
        'end_date' => '2027-06-30',
        'monthly_rent_amount' => $property->monthly_rent_amount,
        'currency' => 'RON',
        'rent_due_day' => 5,
        'deposit_amount' => 2500,
        'status' => 'active',
        'notes' => 'Contract semnat.',
    ], $overrides);
}

test('leases index shows leases for the current workspace', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $otherTeam = Team::factory()->create();

    Lease::factory()->for($team)->create();
    Lease::factory()->for($otherTeam)->create();

    $response = $this
        ->actingAs($user)
        ->get(route('leases.index', $team));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('leases/index')
            ->has('leases', 1)
        );
});

test('workspace members can create leases with renter contact details', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $property = Property::factory()->for($team)->create([
        'monthly_rent_amount' => 2500,
    ]);

    $response = $this
        ->actingAs($user)
        ->post(route('leases.store', $team), validLeasePayload($property));

    $lease = Lease::first();
    $renter = Renter::first();

    $response->assertRedirect(route('leases.index', $team));

    $this->assertDatabaseHas('renters', [
        'team_id' => $team->id,
        'name' => 'Ana Popescu',
        'email' => 'ana@example.com',
    ]);

    $this->assertDatabaseHas('leases', [
        'team_id' => $team->id,
        'property_id' => $property->id,
        'renter_id' => $renter->id,
        'status' => 'active',
        'monthly_rent_amount' => 2500,
        'rent_due_day' => 5,
    ]);
});

test('lease validation requires rent due day', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $property = Property::factory()->for($team)->create([
        'monthly_rent_amount' => 2500,
    ]);

    $response = $this
        ->actingAs($user)
        ->post(route('leases.store', $team), validLeasePayload($property, [
            'rent_due_day' => null,
        ]));

    $response->assertSessionHasErrors(['rent_due_day']);
    $this->assertDatabaseCount('leases', 0);
});

test('can create a lease when no other lease exists for the property', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $property = Property::factory()->for($team)->create([
        'monthly_rent_amount' => 2500,
    ]);

    $response = $this
        ->actingAs($user)
        ->post(route('leases.store', $team), validLeasePayload($property, [
            'start_date' => '2026-07-01',
            'end_date' => '2027-07-01',
        ]));

    $response->assertRedirect(route('leases.index', $team));

    $this->assertDatabaseHas('leases', [
        'property_id' => $property->id,
    ]);
});

test('a lease can be created when rent matches the selected property rent', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $property = Property::factory()->for($team)->create([
        'monthly_rent_amount' => 1111,
    ]);

    $response = $this
        ->actingAs($user)
        ->post(route('leases.store', $team), validLeasePayload($property, [
            'monthly_rent_amount' => 1111,
        ]));

    $response->assertRedirect(route('leases.index', $team));

    $this->assertDatabaseHas('leases', [
        'property_id' => $property->id,
        'monthly_rent_amount' => 1111,
    ]);
});

test('a lease cannot be created with rent different from the selected property rent', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $property = Property::factory()->for($team)->create([
        'monthly_rent_amount' => 1111,
    ]);

    $response = $this
        ->actingAs($user)
        ->post(route('leases.store', $team), validLeasePayload($property, [
            'monthly_rent_amount' => 111,
        ]));

    $response->assertSessionHasErrors([
        'monthly_rent_amount' => 'Chiria contractului trebuie să fie aceeași cu chiria proprietății selectate.',
    ]);

    $this->assertDatabaseCount('leases', 0);
});

test('a lease update cannot change rent to a value different from the selected property rent', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $property = Property::factory()->for($team)->create([
        'monthly_rent_amount' => 1111,
    ]);
    $lease = Lease::factory()->for($team)->create([
        'property_id' => $property->id,
        'monthly_rent_amount' => 1111,
        'status' => 'upcoming',
    ]);

    $response = $this
        ->actingAs($user)
        ->patch(route('leases.update', [$team, $lease]), validLeasePayload($property, [
            'monthly_rent_amount' => 111,
            'status' => 'upcoming',
        ]));

    $response->assertSessionHasErrors([
        'monthly_rent_amount' => 'Chiria contractului trebuie să fie aceeași cu chiria proprietății selectate.',
    ]);

    $this->assertDatabaseHas('leases', [
        'id' => $lease->id,
        'monthly_rent_amount' => 1111,
    ]);
});

test('the saved lease rent equals the selected property rent', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $property = Property::factory()->for($team)->create([
        'monthly_rent_amount' => 1111,
    ]);

    $this
        ->actingAs($user)
        ->post(route('leases.store', $team), validLeasePayload($property, [
            'monthly_rent_amount' => 1111,
        ]))
        ->assertRedirect(route('leases.index', $team));

    expect(Lease::first()->monthly_rent_amount)->toBe('1111.00');
});

test('lease with future start date shows future status', function () {
    Carbon::setTestNow('2026-07-15');

    $user = User::factory()->create();
    $team = $user->currentTeam;
    $lease = Lease::factory()->for($team)->create([
        'start_date' => '2026-08-01',
        'end_date' => '2027-07-31',
        'status' => 'active',
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('leases.show', [$team, $lease]));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('leases/show')
            ->where('lease.status', 'upcoming')
        );

    Carbon::setTestNow();
});

test('lease with current date inside period shows active status', function () {
    Carbon::setTestNow('2026-07-15');

    $user = User::factory()->create();
    $team = $user->currentTeam;
    $lease = Lease::factory()->for($team)->create([
        'start_date' => '2026-07-01',
        'end_date' => '2027-07-01',
        'status' => 'ended',
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('leases.show', [$team, $lease]));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('leases/show')
            ->where('lease.status', 'active')
        );

    Carbon::setTestNow();
});

test('lease with past end date shows closed status', function () {
    Carbon::setTestNow('2026-07-15');

    $user = User::factory()->create();
    $team = $user->currentTeam;
    $lease = Lease::factory()->for($team)->create([
        'start_date' => '2025-07-01',
        'end_date' => '2026-06-30',
        'status' => 'active',
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('leases.show', [$team, $lease]));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('leases/show')
            ->where('lease.status', 'ended')
        );

    Carbon::setTestNow();
});

test('lease validation requires core fields and workspace property', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $property = Property::factory()->for($team)->create();
    $otherProperty = Property::factory()->create();

    $response = $this
        ->actingAs($user)
        ->post(route('leases.store', $team), validLeasePayload($property, [
            'property_id' => $otherProperty->id,
            'renter_name' => '',
            'renter_email' => 'not-an-email',
            'start_date' => '',
            'end_date' => '2026-06-01',
            'monthly_rent_amount' => -1,
            'currency' => '',
            'rent_due_day' => 32,
            'deposit_amount' => -1,
            'status' => 'draft',
        ]));

    $response->assertSessionHasErrors([
        'property_id',
        'renter_name',
        'renter_email',
        'start_date',
        'monthly_rent_amount',
        'currency',
        'rent_due_day',
        'deposit_amount',
    ]);
});

test('cannot create a lease with overlapping dates on the same property', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $property = Property::factory()->for($team)->create([
        'monthly_rent_amount' => 2500,
    ]);

    Lease::factory()->for($team)->create([
        'property_id' => $property->id,
        'start_date' => '2026-07-01',
        'end_date' => '2027-06-30',
        'status' => 'active',
    ]);

    $response = $this
        ->actingAs($user)
        ->post(route('leases.store', $team), validLeasePayload($property, [
            'start_date' => '2026-12-01',
            'end_date' => '2027-12-31',
            'status' => 'active',
        ]));

    $response->assertSessionHasErrors([
        'property_id' => 'Această proprietate are deja un contract în perioada selectată.',
    ]);

    $this->assertDatabaseCount('leases', 1);
});

test('can create a lease on another property for the same dates', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $property = Property::factory()->for($team)->create([
        'monthly_rent_amount' => 2500,
    ]);
    $otherProperty = Property::factory()->for($team)->create([
        'monthly_rent_amount' => 2500,
    ]);

    Lease::factory()->for($team)->create([
        'property_id' => $property->id,
        'start_date' => '2026-07-01',
        'end_date' => '2027-07-01',
        'monthly_rent_amount' => 2500,
        'status' => 'active',
    ]);

    $response = $this
        ->actingAs($user)
        ->post(route('leases.store', $team), validLeasePayload($otherProperty, [
            'start_date' => '2026-07-01',
            'end_date' => '2027-07-01',
        ]));

    $response->assertRedirect(route('leases.index', $team));

    $this->assertDatabaseHas('leases', [
        'property_id' => $otherProperty->id,
    ]);
});

test('can create a lease on the same property after the previous lease ended', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $property = Property::factory()->for($team)->create([
        'monthly_rent_amount' => 2500,
    ]);

    Lease::factory()->for($team)->create([
        'property_id' => $property->id,
        'start_date' => '2026-07-01',
        'end_date' => '2027-07-01',
        'monthly_rent_amount' => 2500,
        'status' => 'ended',
    ]);

    $response = $this
        ->actingAs($user)
        ->post(route('leases.store', $team), validLeasePayload($property, [
            'start_date' => '2027-07-02',
            'end_date' => '2028-07-01',
            'status' => 'upcoming',
        ]));

    $response->assertRedirect(route('leases.index', $team));

    $this->assertDatabaseCount('leases', 2);
});

test('cannot update a lease to overlap another lease on the same property', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $property = Property::factory()->for($team)->create([
        'monthly_rent_amount' => 2500,
    ]);

    Lease::factory()->for($team)->create([
        'property_id' => $property->id,
        'start_date' => '2026-07-01',
        'end_date' => '2027-06-30',
        'status' => 'active',
    ]);

    $lease = Lease::factory()->for($team)->create([
        'property_id' => $property->id,
        'start_date' => '2027-07-01',
        'end_date' => '2028-06-30',
        'status' => 'upcoming',
    ]);

    $response = $this
        ->actingAs($user)
        ->patch(route('leases.update', [$team, $lease]), validLeasePayload($property, [
            'start_date' => '2027-01-01',
            'end_date' => '2027-12-31',
            'status' => 'active',
        ]));

    $response->assertSessionHasErrors([
        'property_id' => 'Această proprietate are deja un contract în perioada selectată.',
    ]);

    $this->assertDatabaseHas('leases', [
        'id' => $lease->id,
        'status' => 'upcoming',
    ]);
});

test('workspace members can view and update leases regardless of team role', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $team = Team::factory()->create();

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
    $team->members()->attach($member, ['role' => TeamRole::Member->value]);

    $property = Property::factory()->for($team)->create([
        'monthly_rent_amount' => 2500,
    ]);
    $lease = Lease::factory()->for($team)->create([
        'property_id' => $property->id,
        'status' => 'upcoming',
    ]);

    $this
        ->actingAs($member)
        ->get(route('leases.show', [$team, $lease]))
        ->assertOk();

    $response = $this
        ->actingAs($member)
        ->patch(route('leases.update', [$team, $lease]), validLeasePayload($property, [
            'renter_name' => 'Maria Ionescu',
            'status' => 'active',
        ]));

    $response->assertRedirect(route('leases.show', [$team, $lease]));

    $this->assertDatabaseHas('leases', [
        'id' => $lease->id,
        'status' => 'active',
    ]);

    $this->assertDatabaseHas('renters', [
        'id' => $lease->renter_id,
        'name' => 'Maria Ionescu',
    ]);
});

test('users cannot access lease workspace they do not belong to', function () {
    $user = User::factory()->create();
    $otherTeam = Team::factory()->create();

    $this->withoutExceptionHandling();

    expect(fn () => $this
        ->actingAs($user)
        ->get(route('leases.index', $otherTeam)))
        ->toThrow(HttpException::class);
});

test('users cannot access leases owned by another workspace', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $otherTeam = Team::factory()->create();
    $lease = Lease::factory()->for($otherTeam)->create();

    $this->withoutExceptionHandling();

    expect(fn () => $this
        ->actingAs($user)
        ->get(route('leases.show', [$team, $lease])))
        ->toThrow(AuthorizationException::class);
});

test('workspace members can delete leases', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $lease = Lease::factory()->for($team)->create();

    $response = $this
        ->actingAs($user)
        ->delete(route('leases.destroy', [$team, $lease]));

    $response->assertRedirect(route('leases.index', $team));

    $this->assertDatabaseMissing('leases', [
        'id' => $lease->id,
    ]);
});
