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
        'payment_type' => 'rent',
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

test('payments index includes payment type label data for rent payments', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $lease = Lease::factory()->for($team)->create([
        'monthly_rent_amount' => 2500,
    ]);

    RentPayment::factory()->for($team)->create([
        'lease_id' => $lease->id,
        'property_id' => $lease->property_id,
        'renter_id' => $lease->renter_id,
        'amount' => 2500,
        'payment_type' => 'rent',
        'status' => 'paid',
    ]);

    $this
        ->actingAs($user)
        ->get(route('payments.index', $team))
        ->assertOk()
        ->assertDontSee(chr(195).chr(130), false)
        ->assertInertia(fn (Assert $page) => $page
            ->component('payments/index')
            ->where('payments.0.payment_type', 'rent')
            ->where('payments.0.guarantee_summary', null)
            ->where('payments.0.status_summary.status_key', 'paid')
        );
});

test('payments index includes guarantee type and partial aggregate guarantee status', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $lease = Lease::factory()->for($team)->create([
        'monthly_rent_amount' => 2500,
        'deposit_amount' => 1000,
    ]);

    RentPayment::factory()->for($team)->create([
        'lease_id' => $lease->id,
        'property_id' => $lease->property_id,
        'renter_id' => $lease->renter_id,
        'amount' => 100,
        'payment_type' => 'guarantee',
        'period_month' => null,
        'period_year' => null,
        'status' => 'paid',
    ]);

    $this
        ->actingAs($user)
        ->get(route('payments.index', $team))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('payments/index')
            ->where('payments.0.payment_type', 'guarantee')
            ->where('payments.0.status', 'paid')
            ->where('payments.0.status_summary.status_key', 'partial')
            ->where('payments.0.guarantee_summary.expected_amount', '1000.00')
            ->where('payments.0.guarantee_summary.collected_amount', '100.00')
            ->where('payments.0.guarantee_summary.remaining_amount', '900.00')
            ->where('payments.0.guarantee_summary.status_key', 'partial')
        );
});

test('payments index marks guarantee as fully paid when guarantee payments reach contract guarantee', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $lease = Lease::factory()->for($team)->create([
        'monthly_rent_amount' => 2500,
        'deposit_amount' => 1000,
    ]);

    RentPayment::factory()->for($team)->create([
        'lease_id' => $lease->id,
        'property_id' => $lease->property_id,
        'renter_id' => $lease->renter_id,
        'amount' => 400,
        'payment_type' => 'guarantee',
        'period_month' => null,
        'period_year' => null,
        'payment_date' => '2026-06-01',
        'status' => 'partial',
    ]);
    RentPayment::factory()->for($team)->create([
        'lease_id' => $lease->id,
        'property_id' => $lease->property_id,
        'renter_id' => $lease->renter_id,
        'amount' => 600,
        'payment_type' => 'guarantee',
        'period_month' => null,
        'period_year' => null,
        'payment_date' => '2026-06-02',
        'status' => 'paid',
    ]);

    $this
        ->actingAs($user)
        ->get(route('payments.index', $team))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('payments/index')
            ->where('payments.0.payment_type', 'guarantee')
            ->where('payments.0.status_summary.status_key', 'paid')
            ->where('payments.0.guarantee_summary.expected_amount', '1000.00')
            ->where('payments.0.guarantee_summary.collected_amount', '1000.00')
            ->where('payments.0.guarantee_summary.remaining_amount', '0.00')
            ->where('payments.0.guarantee_summary.status_key', 'paid')
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
        'payment_type' => 'rent',
    ]);
});

test('payment validation requires core fields and workspace lease', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $lease = Lease::factory()->for($team)->create([
        'monthly_rent_amount' => 2500,
    ]);
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
    ]);
});

test('rent payment partial amount is derived as partial even if submitted status says fully paid', function () {
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

    $response->assertRedirect(route('payments.index', $team));

    $this->assertDatabaseHas('rent_payments', [
        'lease_id' => $lease->id,
        'amount' => 1500,
        'status' => 'partial',
    ]);

    $this
        ->actingAs($user)
        ->get(route('payments.index', $team))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('payments/index')
            ->where('payments.0.status_summary.status_key', 'partial')
        );
});

test('rent payment full amount is derived as fully paid even if submitted status says partial', function () {
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

    $response->assertRedirect(route('payments.index', $team));

    $this->assertDatabaseHas('rent_payments', [
        'lease_id' => $lease->id,
        'amount' => 2500,
        'status' => 'paid',
    ]);

    $this
        ->actingAs($user)
        ->get(route('payments.index', $team))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('payments/index')
            ->where('payments.0.status_summary.status_key', 'paid')
        );
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

test('manual partial status is ignored when rent amount reaches expected total', function () {
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

    $response->assertRedirect(route('payments.index', $team));

    $this->assertDatabaseHas('rent_payments', [
        'lease_id' => $lease->id,
        'amount' => 2500,
        'status' => 'paid',
    ]);
});

test('rent payment above monthly rent amount is saved as fully paid by derived total', function () {
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

    $response->assertRedirect(route('payments.index', $team));

    $this->assertDatabaseHas('rent_payments', [
        'lease_id' => $lease->id,
        'amount' => 2600,
        'status' => 'paid',
    ]);
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

test('guarantee payment can be created without rent period', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $lease = Lease::factory()->for($team)->create([
        'monthly_rent_amount' => 2500,
        'deposit_amount' => 1000,
    ]);

    $response = $this
        ->actingAs($user)
        ->post(route('payments.store', $team), validRentPaymentPayload($lease, [
            'amount' => 1000,
            'payment_type' => 'guarantee',
            'period_month' => null,
            'period_year' => null,
            'status' => 'paid',
        ]));

    $response->assertRedirect(route('payments.index', $team));

    $this->assertDatabaseHas('rent_payments', [
        'team_id' => $team->id,
        'lease_id' => $lease->id,
        'amount' => 1000,
        'payment_type' => 'guarantee',
        'period_month' => null,
        'period_year' => null,
        'status' => 'paid',
    ]);
});

test('backend ignores manually submitted full status for partial guarantee payment', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $lease = Lease::factory()->for($team)->create([
        'monthly_rent_amount' => 2500,
        'deposit_amount' => 1000,
    ]);

    $response = $this
        ->actingAs($user)
        ->post(route('payments.store', $team), validRentPaymentPayload($lease, [
            'amount' => 100,
            'payment_type' => 'guarantee',
            'period_month' => null,
            'period_year' => null,
            'status' => 'paid',
        ]));

    $response->assertRedirect(route('payments.index', $team));

    $this->assertDatabaseHas('rent_payments', [
        'team_id' => $team->id,
        'lease_id' => $lease->id,
        'amount' => 100,
        'payment_type' => 'guarantee',
        'status' => 'partial',
    ]);

    $this
        ->actingAs($user)
        ->get(route('payments.index', $team))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('payments/index')
            ->where('payments.0.status_summary.status_key', 'partial')
            ->where('payments.0.guarantee_summary.collected_amount', '100.00')
            ->where('payments.0.guarantee_summary.expected_amount', '1000.00')
        );
});

test('workspace members can update and delete payments', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $lease = Lease::factory()->for($team)->create([
        'monthly_rent_amount' => 2500,
    ]);
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

test('payment update ignores submitted status and stores derived status', function () {
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

    $response->assertRedirect(route('payments.show', [$team, $payment]));

    $this->assertDatabaseHas('rent_payments', [
        'id' => $payment->id,
        'amount' => 1500,
        'status' => 'partial',
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
