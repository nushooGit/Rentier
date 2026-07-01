<?php

use App\Models\Lease;
use App\Models\Property;
use App\Models\RentPayment;
use App\Models\Team;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

function validRentPaymentPayload(Lease $lease, array $overrides = []): array
{
    return array_merge([
        'lease_id' => $lease->id,
        'amount' => 2500,
        'currency' => 'RON',
        'payment_date' => '2026-06-05',
        'period_month' => 6,
        'period_year' => 2026,
        'method' => 'bank_transfer',
        'status' => 'paid',
        'notes' => 'Încasat integral.',
    ], $overrides);
}

test('payments index shows payments for the current workspace', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $otherTeam = Team::factory()->create();

    RentPayment::factory()->for($team)->create();
    RentPayment::factory()->for($otherTeam)->create();

    $response = $this
        ->actingAs($user)
        ->get(route('payments.index', $team));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('payments/index')
            ->has('payments', 1)
        );
});

test('workspace members can create payments and derived property and renter are trusted from lease', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $lease = Lease::factory()->for($team)->create([
        'monthly_rent_amount' => 2500,
    ]);
    $otherProperty = Property::factory()->for($team)->create();

    $response = $this
        ->actingAs($user)
        ->post(route('payments.store', $team), validRentPaymentPayload($lease, [
            'property_id' => $otherProperty->id,
            'renter_id' => 999999,
        ]));

    $payment = RentPayment::first();

    $response->assertRedirect(route('payments.index', $team));

    $this->assertDatabaseHas('rent_payments', [
        'team_id' => $team->id,
        'lease_id' => $lease->id,
        'property_id' => $lease->property_id,
        'renter_id' => $lease->renter_id,
        'amount' => 2500,
    ]);
});

test('payment validation requires core fields and workspace lease', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $lease = Lease::factory()->for($team)->create();
    $otherLease = Lease::factory()->create();

    $response = $this
        ->actingAs($user)
        ->post(route('payments.store', $team), validRentPaymentPayload($lease, [
            'lease_id' => $otherLease->id,
            'amount' => -1,
            'currency' => '',
            'payment_date' => '',
            'period_month' => 13,
            'period_year' => 1999,
            'method' => 'crypto',
            'status' => 'draft',
        ]));

    $response->assertSessionHasErrors([
        'lease_id',
        'amount',
        'currency',
        'payment_date',
        'period_month',
        'period_year',
        'method',
        'status',
    ]);
});

test('fully paid payment cannot be created below the monthly rent amount', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $lease = Lease::factory()->for($team)->create([
        'monthly_rent_amount' => 2500,
    ]);

    $response = $this
        ->actingAs($user)
        ->post(route('payments.store', $team), validRentPaymentPayload($lease, [
            'amount' => 1500,
            'status' => 'paid',
        ]));

    $response->assertSessionHasErrors([
        'amount' => 'Pentru o plată achitată integral, suma trebuie să fie egală cu chiria lunară.',
    ]);

    $this->assertDatabaseCount('rent_payments', 0);
});

test('fully paid payment cannot be created above the monthly rent amount', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $lease = Lease::factory()->for($team)->create([
        'monthly_rent_amount' => 2500,
    ]);

    $response = $this
        ->actingAs($user)
        ->post(route('payments.store', $team), validRentPaymentPayload($lease, [
            'amount' => 2600,
            'status' => 'paid',
        ]));

    $response->assertSessionHasErrors([
        'amount' => 'Suma introdusă depășește chiria lunară. Plățile în avans vor fi gestionate într-un modul viitor.',
    ]);

    $this->assertDatabaseCount('rent_payments', 0);
});

test('partial payment can be created below the monthly rent amount', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $lease = Lease::factory()->for($team)->create([
        'monthly_rent_amount' => 2500,
    ]);

    $response = $this
        ->actingAs($user)
        ->post(route('payments.store', $team), validRentPaymentPayload($lease, [
            'amount' => 1500,
            'status' => 'partial',
        ]));

    $payment = RentPayment::first();

    $response->assertRedirect(route('payments.index', $team));

    $this->assertDatabaseHas('rent_payments', [
        'team_id' => $team->id,
        'lease_id' => $lease->id,
        'amount' => 1500,
        'status' => 'partial',
    ]);
});

test('partial payment cannot be created when amount equals monthly rent amount', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $lease = Lease::factory()->for($team)->create([
        'monthly_rent_amount' => 2500,
    ]);

    $response = $this
        ->actingAs($user)
        ->post(route('payments.store', $team), validRentPaymentPayload($lease, [
            'amount' => 2500,
            'status' => 'partial',
        ]));

    $response->assertSessionHasErrors([
        'amount' => 'O plată parțială trebuie să fie mai mică decât chiria lunară.',
    ]);

    $this->assertDatabaseCount('rent_payments', 0);
});

test('partial payment cannot be created when amount is above monthly rent amount', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $lease = Lease::factory()->for($team)->create([
        'monthly_rent_amount' => 2500,
    ]);

    $response = $this
        ->actingAs($user)
        ->post(route('payments.store', $team), validRentPaymentPayload($lease, [
            'amount' => 2600,
            'status' => 'partial',
        ]));

    $response->assertSessionHasErrors([
        'amount' => 'Suma introdusă depășește chiria lunară. Plățile în avans vor fi gestionate într-un modul viitor.',
    ]);

    $this->assertDatabaseCount('rent_payments', 0);
});

test('fully paid payment can be created when amount matches monthly rent amount', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $lease = Lease::factory()->for($team)->create([
        'monthly_rent_amount' => 2500,
    ]);

    $response = $this
        ->actingAs($user)
        ->post(route('payments.store', $team), validRentPaymentPayload($lease, [
            'amount' => 2500,
            'status' => 'paid',
        ]));

    $payment = RentPayment::first();

    $response->assertRedirect(route('payments.index', $team));

    $this->assertDatabaseHas('rent_payments', [
        'team_id' => $team->id,
        'lease_id' => $lease->id,
        'amount' => 2500,
        'status' => 'paid',
    ]);
});

test('workspace members can update and delete payments', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $lease = Lease::factory()->for($team)->create();
    $payment = RentPayment::factory()->for($team)->create([
        'lease_id' => $lease->id,
        'property_id' => $lease->property_id,
        'renter_id' => $lease->renter_id,
        'status' => 'pending',
    ]);

    $response = $this
        ->actingAs($user)
        ->patch(route('payments.update', [$team, $payment]), validRentPaymentPayload($lease, [
            'amount' => 1200,
            'status' => 'partial',
        ]));

    $response->assertRedirect(route('payments.show', [$team, $payment]));

    $this->assertDatabaseHas('rent_payments', [
        'id' => $payment->id,
        'amount' => 1200,
        'status' => 'partial',
    ]);

    $this
        ->actingAs($user)
        ->delete(route('payments.destroy', [$team, $payment]))
        ->assertRedirect(route('payments.index', $team));

    $this->assertDatabaseMissing('rent_payments', [
        'id' => $payment->id,
    ]);

    $this->assertDatabaseHas('leases', [
        'id' => $lease->id,
    ]);

    $this->assertDatabaseHas('properties', [
        'id' => $lease->property_id,
    ]);

    $this->assertDatabaseHas('renters', [
        'id' => $lease->renter_id,
    ]);
});

test('payment update validates amount against monthly rent amount', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $lease = Lease::factory()->for($team)->create([
        'monthly_rent_amount' => 2500,
    ]);
    $payment = RentPayment::factory()->for($team)->create([
        'lease_id' => $lease->id,
        'property_id' => $lease->property_id,
        'renter_id' => $lease->renter_id,
        'amount' => 2500,
        'status' => 'paid',
    ]);

    $response = $this
        ->actingAs($user)
        ->patch(route('payments.update', [$team, $payment]), validRentPaymentPayload($lease, [
            'amount' => 1500,
            'status' => 'paid',
        ]));

    $response->assertSessionHasErrors([
        'amount' => 'Pentru o plată achitată integral, suma trebuie să fie egală cu chiria lunară.',
    ]);

    $this->assertDatabaseHas('rent_payments', [
        'id' => $payment->id,
        'amount' => 2500,
        'status' => 'paid',
    ]);
});

test('users cannot access payments owned by another workspace', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $otherTeam = Team::factory()->create();
    $payment = RentPayment::factory()->for($otherTeam)->create();

    $this->withoutExceptionHandling();

    expect(fn () => $this
        ->actingAs($user)
        ->get(route('payments.show', [$team, $payment])))
        ->toThrow(AuthorizationException::class);
});
