<?php

use App\Enums\TeamRole;
use App\Models\Expense;
use App\Models\Lease;
use App\Models\Property;
use App\Models\RentPayment;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('guests are redirected to the login page', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $response = $this->get(route('dashboard', $team));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $response = $this
        ->actingAs($user)
        ->get(route('dashboard', $team));

    $response->assertOk();
});

test('dashboard includes current workspace financial summary', function () {
    Carbon::setTestNow('2026-07-10');

    $user = User::factory()->create();
    $team = $user->currentTeam;
    $paidProperty = Property::factory()->for($team)->create([
        'name' => 'Paid Apartment',
        'monthly_rent_amount' => 2500,
    ]);
    $overdueProperty = Property::factory()->for($team)->create([
        'name' => 'Overdue Studio',
        'monthly_rent_amount' => 1800,
    ]);
    $partialUpcomingProperty = Property::factory()->for($team)->create([
        'name' => 'Upcoming Loft',
        'monthly_rent_amount' => 1200,
    ]);
    Property::factory()->for($team)->create([
        'name' => 'Vacant House',
        'monthly_rent_amount' => 3000,
    ]);

    $paidLease = Lease::factory()->for($team)->create([
        'property_id' => $paidProperty->id,
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'monthly_rent_amount' => 2500,
        'rent_due_day' => 15,
    ]);
    Lease::factory()->for($team)->create([
        'property_id' => $overdueProperty->id,
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'monthly_rent_amount' => 1800,
        'rent_due_day' => 5,
    ]);
    $partialUpcomingLease = Lease::factory()->for($team)->create([
        'property_id' => $partialUpcomingProperty->id,
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'monthly_rent_amount' => 1200,
        'rent_due_day' => 15,
    ]);

    RentPayment::factory()->for($team)->create([
        'lease_id' => $paidLease->id,
        'property_id' => $paidLease->property_id,
        'renter_id' => $paidLease->renter_id,
        'amount' => 2500,
        'period_month' => 7,
        'period_year' => 2026,
        'payment_date' => '2026-07-03',
        'status' => 'paid',
    ]);
    RentPayment::factory()->for($team)->create([
        'lease_id' => $partialUpcomingLease->id,
        'property_id' => $partialUpcomingLease->property_id,
        'renter_id' => $partialUpcomingLease->renter_id,
        'amount' => 400,
        'period_month' => 7,
        'period_year' => 2026,
        'payment_date' => '2026-07-08',
        'status' => 'partial',
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('dashboard', $team));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('dashboard')
        ->where('summary.property_count', 4)
        ->where('summary.active_lease_count', 3)
        ->where('summary.estimated_monthly_rent', '5500.00')
        ->where('summary.current_month_payments', '2900.00')
        ->where('summary.remaining_rent', '2600.00')
        ->where('summary.overdue_count', 1)
        ->where('summary.occupancy_label', '3/4')
        ->where('summary.occupancy_rate', 75)
        ->where('propertyStatusSummary.active', 3)
        ->where('propertyStatusSummary.available', 1)
        ->has('overdueLeases', 1)
        ->where('overdueLeases.0.property_name', 'Overdue Studio')
        ->where('overdueLeases.0.status_key', 'overdue')
        ->where('overdueLeases.0.due_date', '2026-07-05')
        ->where('overdueLeases.0.days', 5)
        ->where('overdueLeases.0.remaining_amount', '1800.00')
        ->has('upcomingPayments', 1)
        ->where('upcomingPayments.0.property_name', 'Upcoming Loft')
        ->where('upcomingPayments.0.status_key', 'partial')
        ->where('upcomingPayments.0.due_date', '2026-07-15')
        ->where('upcomingPayments.0.remaining_amount', '800.00')
        ->has('propertiesWithoutActiveLease', 1)
        ->where('propertiesWithoutActiveLease.0.name', 'Vacant House'),
    );

    Carbon::setTestNow();
});

test('dashboard expense settlement numbers are correct for mixed current month activity', function () {
    Carbon::setTestNow('2026-07-10');

    $user = User::factory()->create();
    $team = $user->currentTeam;
    $lease = Lease::factory()->for($team)->create([
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'monthly_rent_amount' => 2500,
        'rent_due_day' => 15,
    ]);

    RentPayment::factory()->for($team)->create([
        'lease_id' => $lease->id,
        'property_id' => $lease->property_id,
        'renter_id' => $lease->renter_id,
        'amount' => 2000,
        'period_month' => 7,
        'period_year' => 2026,
        'payment_date' => '2026-07-03',
        'status' => 'partial',
    ]);

    Expense::factory()->for($team)->create([
        'property_id' => $lease->property_id,
        'lease_id' => $lease->id,
        'amount' => 400,
        'expense_date' => '2026-07-04',
        'paid_by' => 'owner',
        'responsible_party' => 'owner',
        'settlement_type' => 'none',
        'status' => 'paid',
    ]);

    Expense::factory()->for($team)->create([
        'property_id' => $lease->property_id,
        'lease_id' => $lease->id,
        'amount' => 250,
        'expense_date' => '2026-07-05',
        'paid_by' => 'tenant',
        'responsible_party' => 'tenant',
        'settlement_type' => 'none',
        'status' => 'paid',
    ]);

    Expense::factory()->for($team)->create([
        'property_id' => $lease->property_id,
        'lease_id' => $lease->id,
        'amount' => 300,
        'expense_date' => '2026-07-06',
        'paid_by' => 'tenant',
        'responsible_party' => 'owner',
        'settlement_type' => 'deduct_from_rent',
        'status' => 'paid',
    ]);

    Expense::factory()->for($team)->create([
        'property_id' => $lease->property_id,
        'lease_id' => $lease->id,
        'amount' => 200,
        'expense_date' => '2026-07-07',
        'paid_by' => 'owner',
        'responsible_party' => 'tenant',
        'settlement_type' => 'reimburse',
        'status' => 'paid',
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('dashboard', $team));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('dashboard')
        ->where('summary.current_month_payments', '2000.00')
        ->where('summary.current_month_rent_deductions', '300.00')
        ->where('summary.current_month_covered_rent', '2300.00')
        ->where('summary.remaining_rent', '200.00')
        ->where('summary.current_month_expenses', '700.00')
        ->where('summary.current_month_profit', '1800.00')
        ->where('summary.recoverable_expenses', '200.00')
        ->where('upcomingPayments.0.collected_amount', '2000.00')
        ->where('upcomingPayments.0.rent_deduction_amount', '300.00')
        ->where('upcomingPayments.0.remaining_amount', '200.00')
    );

    Carbon::setTestNow();
});

test('dashboard rent status treats rent deductions as coverage but not cash collected', function () {
    Carbon::setTestNow('2026-07-10');

    $user = User::factory()->create();
    $team = $user->currentTeam;
    $paidLease = Lease::factory()->for($team)->create([
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'monthly_rent_amount' => 2500,
        'rent_due_day' => 5,
    ]);
    $partialLease = Lease::factory()->for($team)->create([
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'monthly_rent_amount' => 2500,
        'rent_due_day' => 15,
    ]);

    RentPayment::factory()->for($team)->create([
        'lease_id' => $paidLease->id,
        'property_id' => $paidLease->property_id,
        'renter_id' => $paidLease->renter_id,
        'amount' => 2200,
        'period_month' => 7,
        'period_year' => 2026,
        'payment_date' => '2026-07-03',
        'status' => 'partial',
    ]);
    Expense::factory()->for($team)->create([
        'property_id' => $paidLease->property_id,
        'lease_id' => $paidLease->id,
        'amount' => 300,
        'expense_date' => '2026-07-04',
        'paid_by' => 'tenant',
        'responsible_party' => 'owner',
        'settlement_type' => 'deduct_from_rent',
        'status' => 'paid',
    ]);

    RentPayment::factory()->for($team)->create([
        'lease_id' => $partialLease->id,
        'property_id' => $partialLease->property_id,
        'renter_id' => $partialLease->renter_id,
        'amount' => 1000,
        'period_month' => 7,
        'period_year' => 2026,
        'payment_date' => '2026-07-03',
        'status' => 'partial',
    ]);
    Expense::factory()->for($team)->create([
        'property_id' => $partialLease->property_id,
        'lease_id' => $partialLease->id,
        'amount' => 300,
        'expense_date' => '2026-07-04',
        'paid_by' => 'tenant',
        'responsible_party' => 'owner',
        'settlement_type' => 'deduct_from_rent',
        'status' => 'paid',
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('dashboard', $team));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('dashboard')
        ->where('summary.current_month_payments', '3200.00')
        ->where('summary.current_month_rent_deductions', '600.00')
        ->where('summary.remaining_rent', '1200.00')
        ->has('overdueLeases', 0)
        ->has('upcomingPayments', 1)
        ->where('upcomingPayments.0.lease_id', $partialLease->id)
        ->where('upcomingPayments.0.status_key', 'partial')
        ->where('upcomingPayments.0.remaining_amount', '1200.00')
    );

    Carbon::setTestNow();
});

test('dashboard separates tenant paid owner expenses that are not rent deductions', function () {
    Carbon::setTestNow('2026-07-10');

    $user = User::factory()->create();
    $team = $user->currentTeam;
    $lease = Lease::factory()->for($team)->create([
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'monthly_rent_amount' => 2500,
        'rent_due_day' => 15,
    ]);

    RentPayment::factory()->for($team)->create([
        'lease_id' => $lease->id,
        'property_id' => $lease->property_id,
        'renter_id' => $lease->renter_id,
        'amount' => 1000,
        'period_month' => 7,
        'period_year' => 2026,
        'payment_date' => '2026-07-03',
        'status' => 'partial',
    ]);

    Expense::factory()->for($team)->create([
        'property_id' => $lease->property_id,
        'lease_id' => $lease->id,
        'amount' => 300,
        'expense_date' => '2026-07-04',
        'paid_by' => 'tenant',
        'responsible_party' => 'owner',
        'settlement_type' => 'reimburse',
        'status' => 'paid',
    ]);

    Expense::factory()->for($team)->create([
        'property_id' => $lease->property_id,
        'lease_id' => $lease->id,
        'amount' => 150,
        'expense_date' => '2026-07-04',
        'paid_by' => 'tenant',
        'responsible_party' => 'owner',
        'settlement_type' => 'deduct_from_utilities',
        'status' => 'paid',
    ]);

    Expense::factory()->for($team)->create([
        'property_id' => $lease->property_id,
        'lease_id' => $lease->id,
        'amount' => 250,
        'expense_date' => '2026-07-04',
        'paid_by' => 'tenant',
        'responsible_party' => 'tenant',
        'settlement_type' => 'none',
        'status' => 'paid',
    ]);

    Expense::factory()->for($team)->create([
        'property_id' => $lease->property_id,
        'lease_id' => $lease->id,
        'amount' => 200,
        'expense_date' => '2026-07-04',
        'paid_by' => 'owner',
        'responsible_party' => 'tenant',
        'settlement_type' => 'reimburse',
        'status' => 'paid',
    ]);

    Expense::factory()->for($team)->create([
        'property_id' => $lease->property_id,
        'lease_id' => $lease->id,
        'amount' => 400,
        'expense_date' => '2026-07-04',
        'paid_by' => 'owner',
        'responsible_party' => 'owner',
        'settlement_type' => 'none',
        'status' => 'paid',
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('dashboard', $team));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('dashboard')
        ->where('summary.current_month_payments', '1000.00')
        ->where('summary.current_month_rent_deductions', '0.00')
        ->where('summary.current_month_covered_rent', '1000.00')
        ->where('summary.remaining_rent', '1500.00')
        ->where('summary.current_month_expenses', '850.00')
        ->where('summary.tenant_reimbursement_expenses', '300.00')
        ->where('summary.utility_deduction_expenses', '150.00')
        ->where('summary.recoverable_expenses', '200.00')
        ->where('summary.unsettled_tenant_paid_owner_expenses', '0.00')
        ->where('upcomingPayments.0.collected_amount', '1000.00')
        ->where('upcomingPayments.0.rent_deduction_amount', '0.00')
        ->where('upcomingPayments.0.remaining_amount', '1500.00')
    );

    Carbon::setTestNow();
});

test('dashboard keeps unsettled recoveries outstanding until they are marked recovered', function () {
    Carbon::setTestNow('2026-07-10');

    $user = User::factory()->create();
    $team = $user->currentTeam;
    $lease = Lease::factory()->for($team)->create([
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'monthly_rent_amount' => 1000,
        'deposit_amount' => 1000,
        'rent_due_day' => 5,
    ]);

    RentPayment::factory()->for($team)->create([
        'lease_id' => $lease->id,
        'property_id' => $lease->property_id,
        'renter_id' => $lease->renter_id,
        'amount' => 150,
        'payment_type' => 'rent',
        'period_month' => 7,
        'period_year' => 2026,
        'payment_date' => '2026-07-03',
    ]);

    Expense::factory()->for($team)->create([
        'property_id' => $lease->property_id,
        'lease_id' => $lease->id,
        'amount' => 500,
        'expense_date' => '2026-07-05',
        'paid_by' => 'owner',
        'responsible_party' => 'owner',
        'settlement_type' => 'none',
        'status' => 'paid',
    ]);

    Expense::factory()->for($team)->create([
        'property_id' => $lease->property_id,
        'lease_id' => $lease->id,
        'amount' => 200,
        'expense_date' => '2026-07-05',
        'paid_by' => 'tenant',
        'responsible_party' => 'owner',
        'settlement_type' => 'deduct_from_rent',
        'status' => 'paid',
    ]);

    $recovery = Expense::factory()->for($team)->create([
        'property_id' => $lease->property_id,
        'lease_id' => $lease->id,
        'amount' => 120,
        'expense_date' => '2026-07-06',
        'paid_by' => 'owner',
        'responsible_party' => 'tenant',
        'settlement_type' => 'reimburse',
        'settled_at' => null,
        'status' => 'reimbursable',
    ]);

    $this
        ->actingAs($user)
        ->get(route('expenses.index', $team))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('expenses.0.id', $recovery->id)
            ->where('expenses.0.settlement_state.kind', 'recovery_due')
            ->where('expenses.0.settlement_state.label', 'De recuperat')
        );

    $this
        ->actingAs($user)
        ->get(route('dashboard', $team))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary.current_month_payments', '150.00')
            ->where('summary.current_month_rent_deductions', '200.00')
            ->where('summary.remaining_rent', '650.00')
            ->where('summary.remaining_guarantees', '1000.00')
            ->where('summary.recoverable_expenses', '120.00')
            ->where('summary.total_receivable', '1770.00')
            ->where('summary.operational_cash_result', '-470.00')
            ->where('summary.collected_guarantees', '0.00')
        );

    $this
        ->actingAs($user)
        ->patch(route('expenses.mark-recovered', [$team, $recovery]))
        ->assertRedirect();

    $this
        ->actingAs($user)
        ->get(route('expenses.index', $team))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('expenses.0.id', $recovery->id)
            ->where('expenses.0.settlement_state.kind', 'recovered')
            ->where('expenses.0.settlement_state.label', 'Recuperat')
        );

    $this
        ->actingAs($user)
        ->get(route('dashboard', $team))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary.recoverable_expenses', '0.00')
            ->where('summary.total_receivable', '1650.00')
            ->where('summary.operational_cash_result', '-350.00')
            ->where('summary.current_month_payments', '150.00')
            ->where('summary.collected_guarantees', '0.00')
        );

    $this
        ->actingAs($user)
        ->patch(route('expenses.undo-recovered', [$team, $recovery]))
        ->assertRedirect();

    $this
        ->actingAs($user)
        ->get(route('dashboard', $team))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary.recoverable_expenses', '120.00')
            ->where('summary.total_receivable', '1770.00')
            ->where('summary.operational_cash_result', '-470.00')
        );

    Carbon::setTestNow();
});

test('dashboard keeps reimbursements outstanding until they are marked reimbursed', function () {
    Carbon::setTestNow('2026-07-10');

    $user = User::factory()->create();
    $team = $user->currentTeam;
    $lease = Lease::factory()->for($team)->create([
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'monthly_rent_amount' => 0,
        'deposit_amount' => 0,
    ]);
    $reimbursement = Expense::factory()->for($team)->create([
        'property_id' => $lease->property_id,
        'lease_id' => $lease->id,
        'amount' => 80,
        'expense_date' => '2026-07-06',
        'paid_by' => 'tenant',
        'responsible_party' => 'owner',
        'settlement_type' => 'reimburse',
        'settled_at' => null,
        'status' => 'reimbursable',
    ]);

    $this
        ->actingAs($user)
        ->get(route('expenses.index', $team))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('expenses.0.id', $reimbursement->id)
            ->where('expenses.0.settlement_state.kind', 'reimbursement_due')
            ->where('expenses.0.settlement_state.label', 'De rambursat')
        );

    $this
        ->actingAs($user)
        ->get(route('dashboard', $team))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary.tenant_reimbursement_expenses', '80.00')
            ->where('summary.operational_cash_result', '0.00')
            ->where('summary.current_month_payments', '0.00')
            ->where('summary.collected_guarantees', '0.00')
        );

    $this
        ->actingAs($user)
        ->patch(route('expenses.mark-reimbursed', [$team, $reimbursement]))
        ->assertRedirect();

    $this
        ->actingAs($user)
        ->get(route('expenses.index', $team))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('expenses.0.id', $reimbursement->id)
            ->where('expenses.0.settlement_state.kind', 'reimbursed')
            ->where('expenses.0.settlement_state.label', 'Rambursat')
        );

    $this
        ->actingAs($user)
        ->get(route('dashboard', $team))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary.tenant_reimbursement_expenses', '0.00')
            ->where('summary.operational_cash_result', '-80.00')
            ->where('summary.current_month_payments', '0.00')
            ->where('summary.collected_guarantees', '0.00')
        );

    $this
        ->actingAs($user)
        ->patch(route('expenses.undo-reimbursed', [$team, $reimbursement]))
        ->assertRedirect();

    $this
        ->actingAs($user)
        ->get(route('dashboard', $team))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary.tenant_reimbursement_expenses', '80.00')
            ->where('summary.operational_cash_result', '0.00')
        );

    Carbon::setTestNow();
});

test('dashboard does not subtract unsettled reimbursement cash before owner reimburses tenant', function () {
    Carbon::setTestNow('2026-07-10');

    $user = User::factory()->create();
    $team = $user->currentTeam;
    $lease = Lease::factory()->for($team)->create([
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'monthly_rent_amount' => 1000,
        'deposit_amount' => 0,
        'rent_due_day' => 5,
    ]);

    RentPayment::factory()->for($team)->create([
        'lease_id' => $lease->id,
        'property_id' => $lease->property_id,
        'renter_id' => $lease->renter_id,
        'amount' => 150,
        'payment_type' => 'rent',
        'period_month' => 7,
        'period_year' => 2026,
        'payment_date' => '2026-07-03',
    ]);

    Expense::factory()->for($team)->create([
        'property_id' => $lease->property_id,
        'lease_id' => $lease->id,
        'amount' => 500,
        'expense_date' => '2026-07-05',
        'paid_by' => 'owner',
        'responsible_party' => 'owner',
        'settlement_type' => 'none',
        'status' => 'paid',
    ]);

    Expense::factory()->for($team)->create([
        'property_id' => $lease->property_id,
        'lease_id' => $lease->id,
        'amount' => 120,
        'expense_date' => '2026-07-06',
        'paid_by' => 'owner',
        'responsible_party' => 'tenant',
        'settlement_type' => 'reimburse',
        'settled_at' => null,
        'status' => 'reimbursable',
    ]);

    $reimbursement = Expense::factory()->for($team)->create([
        'property_id' => $lease->property_id,
        'lease_id' => $lease->id,
        'amount' => 200,
        'expense_date' => '2026-07-07',
        'paid_by' => 'tenant',
        'responsible_party' => 'owner',
        'settlement_type' => 'reimburse',
        'settled_at' => null,
        'status' => 'reimbursable',
    ]);

    $this
        ->actingAs($user)
        ->get(route('expenses.index', $team))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('expenses.0.id', $reimbursement->id)
            ->where('expenses.0.settlement_state.kind', 'reimbursement_due')
            ->where('expenses.0.settlement_state.label', 'De rambursat')
            ->where('expenses.0.settlement_state.action_label', 'Marchează ca rambursat')
        );

    $this
        ->actingAs($user)
        ->get(route('dashboard', $team))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary.current_month_payments', '150.00')
            ->where('summary.tenant_reimbursement_expenses', '200.00')
            ->where('summary.recoverable_expenses', '120.00')
            ->where('summary.operational_cash_result', '-470.00')
            ->where('summary.collected_guarantees', '0.00')
        );

    $this
        ->actingAs($user)
        ->patch(route('expenses.mark-reimbursed', [$team, $reimbursement]))
        ->assertRedirect();

    $this
        ->actingAs($user)
        ->get(route('expenses.index', $team))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('expenses.0.id', $reimbursement->id)
            ->where('expenses.0.settlement_state.kind', 'reimbursed')
            ->where('expenses.0.settlement_state.label', 'Rambursat')
        );

    $this
        ->actingAs($user)
        ->get(route('dashboard', $team))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary.tenant_reimbursement_expenses', '0.00')
            ->where('summary.recoverable_expenses', '120.00')
            ->where('summary.operational_cash_result', '-670.00')
            ->where('summary.current_month_payments', '150.00')
            ->where('summary.collected_guarantees', '0.00')
        );

    $this
        ->actingAs($user)
        ->patch(route('expenses.undo-reimbursed', [$team, $reimbursement]))
        ->assertRedirect();

    $this
        ->actingAs($user)
        ->get(route('dashboard', $team))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary.tenant_reimbursement_expenses', '200.00')
            ->where('summary.operational_cash_result', '-470.00')
        );

    Carbon::setTestNow();
});

test('dashboard excludes settled reimbursements and recoveries from outstanding totals', function () {
    Carbon::setTestNow('2026-07-10');

    $user = User::factory()->create();
    $team = $user->currentTeam;
    $lease = Lease::factory()->for($team)->create([
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'monthly_rent_amount' => 1000,
        'deposit_amount' => 0,
    ]);

    $reimbursement = Expense::factory()->for($team)->create([
        'property_id' => $lease->property_id,
        'lease_id' => $lease->id,
        'amount' => 150,
        'expense_date' => '2026-07-04',
        'paid_by' => 'tenant',
        'responsible_party' => 'owner',
        'settlement_type' => 'reimburse',
        'status' => 'reimbursable',
    ]);
    $recovery = Expense::factory()->for($team)->create([
        'property_id' => $lease->property_id,
        'lease_id' => $lease->id,
        'amount' => 120,
        'expense_date' => '2026-07-04',
        'paid_by' => 'owner',
        'responsible_party' => 'tenant',
        'settlement_type' => 'reimburse',
        'status' => 'reimbursable',
    ]);

    $this
        ->actingAs($user)
        ->get(route('dashboard', $team))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary.tenant_reimbursement_expenses', '150.00')
            ->where('summary.recoverable_expenses', '120.00')
            ->where('summary.total_receivable', '1120.00')
        );

    $this->actingAs($user)->patch(route('expenses.mark-reimbursed', [$team, $reimbursement]));
    $this->actingAs($user)->patch(route('expenses.mark-recovered', [$team, $recovery]));

    $this
        ->actingAs($user)
        ->get(route('dashboard', $team))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary.tenant_reimbursement_expenses', '0.00')
            ->where('summary.recoverable_expenses', '0.00')
            ->where('summary.total_receivable', '1000.00')
        );

    Carbon::setTestNow();
});

test('dashboard groups current month rent payments by payment method', function () {
    Carbon::setTestNow('2026-07-10');

    $user = User::factory()->create();
    $team = $user->currentTeam;
    $lease = Lease::factory()->for($team)->create([
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'monthly_rent_amount' => 3000,
        'deposit_amount' => 0,
        'rent_due_day' => 15,
    ]);

    foreach ([
        ['amount' => 1000, 'method' => 'cash'],
        ['amount' => 750, 'method' => 'bank_transfer'],
        ['amount' => 250, 'method' => 'card'],
        ['amount' => 125, 'method' => null],
    ] as $payment) {
        RentPayment::factory()->for($team)->create([
            'lease_id' => $lease->id,
            'property_id' => $lease->property_id,
            'renter_id' => $lease->renter_id,
            'amount' => $payment['amount'],
            'payment_type' => 'rent',
            'period_month' => 7,
            'period_year' => 2026,
            'payment_date' => '2026-07-05',
            'method' => $payment['method'],
        ]);
    }

    RentPayment::factory()->for($team)->create([
        'lease_id' => $lease->id,
        'property_id' => $lease->property_id,
        'renter_id' => $lease->renter_id,
        'amount' => 900,
        'payment_type' => 'guarantee',
        'period_month' => null,
        'period_year' => null,
        'payment_date' => '2026-07-05',
        'method' => 'cash',
    ]);

    Expense::factory()->for($team)->create([
        'property_id' => $lease->property_id,
        'lease_id' => $lease->id,
        'amount' => 300,
        'expense_date' => '2026-07-04',
        'paid_by' => 'tenant',
        'responsible_party' => 'owner',
        'settlement_type' => 'deduct_from_rent',
        'status' => 'paid',
    ]);

    $this
        ->actingAs($user)
        ->get(route('dashboard', $team))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary.current_month_payments', '2125.00')
            ->where('summary.current_month_rent_deductions', '300.00')
            ->where('rentPaymentMethodBreakdown.0.method', 'bank_transfer')
            ->where('rentPaymentMethodBreakdown.0.label', 'Transfer bancar')
            ->where('rentPaymentMethodBreakdown.0.amount', '750.00')
            ->where('rentPaymentMethodBreakdown.1.method', 'card')
            ->where('rentPaymentMethodBreakdown.1.label', 'Card')
            ->where('rentPaymentMethodBreakdown.1.amount', '250.00')
            ->where('rentPaymentMethodBreakdown.2.method', 'cash')
            ->where('rentPaymentMethodBreakdown.2.label', 'Numerar')
            ->where('rentPaymentMethodBreakdown.2.amount', '1000.00')
            ->where('rentPaymentMethodBreakdown.3.method', null)
            ->where('rentPaymentMethodBreakdown.3.label', 'Nesetat')
            ->where('rentPaymentMethodBreakdown.3.amount', '125.00')
            ->has('rentPaymentMethodBreakdown', 4)
        );

    Carbon::setTestNow();
});

test('settled reimbursement and recovery affect operational cash in settlement month without changing rent collected', function () {
    Carbon::setTestNow('2026-07-10');

    $user = User::factory()->create();
    $team = $user->currentTeam;
    $lease = Lease::factory()->for($team)->create([
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'monthly_rent_amount' => 1000,
        'deposit_amount' => 0,
    ]);

    Expense::factory()->for($team)->create([
        'property_id' => $lease->property_id,
        'lease_id' => $lease->id,
        'amount' => 150,
        'expense_date' => '2026-06-20',
        'paid_by' => 'tenant',
        'responsible_party' => 'owner',
        'settlement_type' => 'reimburse',
        'status' => 'paid',
        'settled_at' => '2026-07-04 10:00:00',
    ]);
    Expense::factory()->for($team)->create([
        'property_id' => $lease->property_id,
        'lease_id' => $lease->id,
        'amount' => 120,
        'expense_date' => '2026-06-20',
        'paid_by' => 'owner',
        'responsible_party' => 'tenant',
        'settlement_type' => 'reimburse',
        'status' => 'paid',
        'settled_at' => '2026-07-04 10:00:00',
    ]);

    $this
        ->actingAs($user)
        ->get(route('dashboard', $team))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary.current_month_payments', '0.00')
            ->where('summary.recoverable_expenses', '0.00')
            ->where('summary.tenant_reimbursement_expenses', '0.00')
            ->where('summary.operational_cash_result', '-30.00')
        );

    Carbon::setTestNow();
});

test('dashboard uses last valid month day for rent due day 31', function () {
    Carbon::setTestNow('2027-02-28');

    $user = User::factory()->create();
    $team = $user->currentTeam;
    $property = Property::factory()->for($team)->create([
        'name' => 'February Apartment',
        'monthly_rent_amount' => 2500,
    ]);

    Lease::factory()->for($team)->create([
        'property_id' => $property->id,
        'start_date' => '2027-01-01',
        'end_date' => '2027-12-31',
        'monthly_rent_amount' => 2500,
        'rent_due_day' => 31,
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('dashboard', $team));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('dashboard')
        ->where('summary.estimated_monthly_rent', '2500.00')
        ->where('summary.current_month_payments', '0.00')
        ->where('summary.remaining_rent', '2500.00')
        ->where('summary.overdue_count', 0)
        ->has('upcomingPayments', 1)
        ->where('upcomingPayments.0.property_name', 'February Apartment')
        ->where('upcomingPayments.0.status_key', 'due_today')
        ->where('upcomingPayments.0.due_date', '2027-02-28')
    );

    Carbon::setTestNow();
});

test('dashboard rent status uses local calendar days for upcoming rent', function () {
    Carbon::setTestNow(Carbon::parse('2026-07-04 02:20:00', 'Europe/Bucharest'));

    $user = User::factory()->create();
    $team = $user->currentTeam;
    $lease = Lease::factory()->for($team)->create([
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'monthly_rent_amount' => 2500,
        'rent_due_day' => 5,
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('dashboard', $team));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('dashboard')
        ->has('upcomingPayments', 1)
        ->where('upcomingPayments.0.lease_id', $lease->id)
        ->where('upcomingPayments.0.status_key', 'upcoming')
        ->where('upcomingPayments.0.due_date', '2026-07-05')
        ->where('upcomingPayments.0.days', 1)
    );

    Carbon::setTestNow();
});

test('dashboard rent status marks due today using local calendar date', function () {
    Carbon::setTestNow(Carbon::parse('2026-07-05 02:20:00', 'Europe/Bucharest'));

    $user = User::factory()->create();
    $team = $user->currentTeam;
    $lease = Lease::factory()->for($team)->create([
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'monthly_rent_amount' => 2500,
        'rent_due_day' => 5,
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('dashboard', $team));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('dashboard')
        ->has('upcomingPayments', 1)
        ->where('upcomingPayments.0.lease_id', $lease->id)
        ->where('upcomingPayments.0.status_key', 'due_today')
        ->where('upcomingPayments.0.days', 0)
    );

    Carbon::setTestNow();
});

test('dashboard rent status marks overdue after local due date', function () {
    Carbon::setTestNow(Carbon::parse('2026-07-06 02:20:00', 'Europe/Bucharest'));

    $user = User::factory()->create();
    $team = $user->currentTeam;
    $lease = Lease::factory()->for($team)->create([
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'monthly_rent_amount' => 2500,
        'rent_due_day' => 5,
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('dashboard', $team));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('dashboard')
        ->has('overdueLeases', 1)
        ->where('overdueLeases.0.lease_id', $lease->id)
        ->where('overdueLeases.0.status_key', 'overdue')
        ->where('overdueLeases.0.days', 1)
    );

    Carbon::setTestNow();
});

test('dashboard counts vacant owner expenses without expected rent', function () {
    Carbon::setTestNow('2026-07-10');

    $user = User::factory()->create();
    $team = $user->currentTeam;
    $property = Property::factory()->for($team)->create([
        'monthly_rent_amount' => 3000,
    ]);

    Expense::factory()->for($team)->create([
        'property_id' => $property->id,
        'lease_id' => null,
        'amount' => 900,
        'expense_date' => '2026-07-04',
        'paid_by' => 'owner',
        'responsible_party' => 'owner',
        'settlement_type' => 'none',
        'status' => 'paid',
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('dashboard', $team));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('dashboard')
        ->where('summary.estimated_monthly_rent', '0.00')
        ->where('summary.current_month_expenses', '900.00')
        ->where('summary.remaining_rent', '0.00')
        ->has('propertiesWithoutActiveLease', 1)
    );

    Carbon::setTestNow();
});

test('dashboard uses contract guarantee instead of property guarantee', function () {
    Carbon::setTestNow('2026-07-10');

    $user = User::factory()->create();
    $team = $user->currentTeam;
    $property = Property::factory()->for($team)->create([
        'deposit_amount' => 1000,
    ]);

    Lease::factory()->for($team)->create([
        'property_id' => $property->id,
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'monthly_rent_amount' => 1000,
        'deposit_amount' => 500,
    ]);

    $this
        ->actingAs($user)
        ->get(route('dashboard', $team))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary.expected_guarantees', '500.00')
            ->where('summary.remaining_guarantees', '500.00')
        );

    Carbon::setTestNow();
});

test('guarantee payment does not count as rent', function () {
    Carbon::setTestNow('2026-07-10');

    $user = User::factory()->create();
    $team = $user->currentTeam;
    $lease = Lease::factory()->for($team)->create([
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'monthly_rent_amount' => 1000,
        'deposit_amount' => 500,
    ]);

    RentPayment::factory()->for($team)->create([
        'lease_id' => $lease->id,
        'property_id' => $lease->property_id,
        'renter_id' => $lease->renter_id,
        'amount' => 500,
        'payment_type' => 'guarantee',
        'period_month' => null,
        'period_year' => null,
        'payment_date' => '2026-07-03',
        'status' => 'paid',
    ]);

    $this
        ->actingAs($user)
        ->get(route('dashboard', $team))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary.current_month_payments', '0.00')
            ->where('summary.collected_guarantees', '500.00')
            ->where('summary.remaining_rent', '1000.00')
            ->where('summary.remaining_guarantees', '0.00')
        );

    Carbon::setTestNow();
});

test('dashboard tracks partial guarantee remaining', function () {
    Carbon::setTestNow('2026-07-10');

    $user = User::factory()->create();
    $team = $user->currentTeam;
    $lease = Lease::factory()->for($team)->create([
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'monthly_rent_amount' => 1000,
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
        'payment_date' => '2026-07-03',
    ]);

    $this
        ->actingAs($user)
        ->get(route('dashboard', $team))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary.expected_guarantees', '1000.00')
            ->where('summary.collected_guarantees', '400.00')
            ->where('summary.remaining_guarantees', '600.00')
        );

    Carbon::setTestNow();
});

test('rent payment still counts toward rent remaining', function () {
    Carbon::setTestNow('2026-07-10');

    $user = User::factory()->create();
    $team = $user->currentTeam;
    $lease = Lease::factory()->for($team)->create([
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'monthly_rent_amount' => 1000,
        'deposit_amount' => 500,
    ]);

    RentPayment::factory()->for($team)->create([
        'lease_id' => $lease->id,
        'property_id' => $lease->property_id,
        'renter_id' => $lease->renter_id,
        'amount' => 400,
        'payment_type' => 'rent',
        'period_month' => 7,
        'period_year' => 2026,
        'payment_date' => '2026-07-03',
        'status' => 'partial',
    ]);

    $this
        ->actingAs($user)
        ->get(route('dashboard', $team))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary.current_month_payments', '400.00')
            ->where('summary.remaining_rent', '600.00')
        );

    Carbon::setTestNow();
});

test('guarantee payments do not affect estimated profit', function () {
    Carbon::setTestNow('2026-07-10');

    $user = User::factory()->create();
    $team = $user->currentTeam;
    $lease = Lease::factory()->for($team)->create([
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'monthly_rent_amount' => 1000,
        'deposit_amount' => 1000,
    ]);

    RentPayment::factory()->for($team)->create([
        'lease_id' => $lease->id,
        'property_id' => $lease->property_id,
        'renter_id' => $lease->renter_id,
        'amount' => 1000,
        'payment_type' => 'guarantee',
        'period_month' => null,
        'period_year' => null,
        'payment_date' => '2026-07-03',
    ]);

    Expense::factory()->for($team)->create([
        'property_id' => $lease->property_id,
        'lease_id' => $lease->id,
        'amount' => 100,
        'expense_date' => '2026-07-04',
        'paid_by' => 'owner',
        'responsible_party' => 'owner',
        'settlement_type' => 'none',
        'status' => 'paid',
    ]);

    $this
        ->actingAs($user)
        ->get(route('dashboard', $team))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary.current_month_profit', '900.00')
            ->where('summary.current_month_payments', '0.00')
        );

    Carbon::setTestNow();
});

test('tenant responsible expenses do not reduce estimated profit', function () {
    Carbon::setTestNow('2026-07-10');

    $user = User::factory()->create();
    $team = $user->currentTeam;
    $lease = Lease::factory()->for($team)->create([
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'monthly_rent_amount' => 1000,
    ]);

    Expense::factory()->for($team)->create([
        'property_id' => $lease->property_id,
        'lease_id' => $lease->id,
        'amount' => 300,
        'expense_date' => '2026-07-04',
        'paid_by' => 'owner',
        'responsible_party' => 'tenant',
        'settlement_type' => 'reimburse',
        'status' => 'reimbursable',
    ]);

    $this
        ->actingAs($user)
        ->get(route('dashboard', $team))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary.current_month_profit', '1000.00')
            ->where('summary.recoverable_expenses', '300.00')
        );

    Carbon::setTestNow();
});

test('dashboard operational cash excludes guarantees and subtracts owner paid expenses', function () {
    Carbon::setTestNow('2026-07-10');

    $user = User::factory()->create();
    $team = $user->currentTeam;
    $lease = Lease::factory()->for($team)->create([
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'monthly_rent_amount' => 1000,
        'deposit_amount' => 1000,
    ]);

    RentPayment::factory()->for($team)->create([
        'lease_id' => $lease->id,
        'property_id' => $lease->property_id,
        'renter_id' => $lease->renter_id,
        'amount' => 700,
        'payment_type' => 'rent',
        'period_month' => 7,
        'period_year' => 2026,
        'payment_date' => '2026-07-03',
        'status' => 'partial',
    ]);
    RentPayment::factory()->for($team)->create([
        'lease_id' => $lease->id,
        'property_id' => $lease->property_id,
        'renter_id' => $lease->renter_id,
        'amount' => 1000,
        'payment_type' => 'guarantee',
        'period_month' => null,
        'period_year' => null,
        'payment_date' => '2026-07-03',
    ]);

    Expense::factory()->for($team)->create([
        'property_id' => $lease->property_id,
        'lease_id' => $lease->id,
        'amount' => 120,
        'expense_date' => '2026-07-04',
        'paid_by' => 'owner',
        'responsible_party' => 'owner',
        'settlement_type' => 'none',
        'status' => 'paid',
    ]);
    Expense::factory()->for($team)->create([
        'property_id' => $lease->property_id,
        'lease_id' => $lease->id,
        'amount' => 80,
        'expense_date' => '2026-07-04',
        'paid_by' => 'owner',
        'responsible_party' => 'tenant',
        'settlement_type' => 'reimburse',
        'status' => 'reimbursable',
    ]);
    Expense::factory()->for($team)->create([
        'property_id' => $lease->property_id,
        'lease_id' => $lease->id,
        'amount' => 50,
        'expense_date' => '2026-07-04',
        'paid_by' => 'tenant',
        'responsible_party' => 'owner',
        'settlement_type' => 'reimburse',
        'status' => 'reimbursable',
    ]);

    $this
        ->actingAs($user)
        ->get(route('dashboard', $team))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary.current_month_payments', '700.00')
            ->where('summary.operational_cash_result', '500.00')
            ->where('summary.collected_guarantees', '1000.00')
        );

    Carbon::setTestNow();
});

test('rent deductions do not affect guarantee metrics or cash collected', function () {
    Carbon::setTestNow('2026-07-10');

    $user = User::factory()->create();
    $team = $user->currentTeam;
    $lease = Lease::factory()->for($team)->create([
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'monthly_rent_amount' => 1000,
        'deposit_amount' => 500,
    ]);

    Expense::factory()->for($team)->create([
        'property_id' => $lease->property_id,
        'lease_id' => $lease->id,
        'amount' => 200,
        'expense_date' => '2026-07-04',
        'paid_by' => 'tenant',
        'responsible_party' => 'owner',
        'settlement_type' => 'deduct_from_rent',
        'status' => 'paid',
    ]);

    $this
        ->actingAs($user)
        ->get(route('dashboard', $team))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary.current_month_payments', '0.00')
            ->where('summary.current_month_rent_deductions', '200.00')
            ->where('summary.remaining_rent', '800.00')
            ->where('summary.expected_guarantees', '500.00')
            ->where('summary.collected_guarantees', '0.00')
            ->where('summary.remaining_guarantees', '500.00')
        );

    Carbon::setTestNow();
});

test('dashboard includes pending invitations for the authenticated user', function () {
    $owner = User::factory()->create(['name' => 'Taylor Otwell']);
    $invitedUser = User::factory()->create(['email' => 'invited@example.com']);
    $team = Team::factory()->create(['name' => 'Laravel Team']);

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $invitation = TeamInvitation::factory()->create([
        'team_id' => $team->id,
        'email' => 'invited@example.com',
        'invited_by' => $owner->id,
    ]);

    $response = $this
        ->actingAs($invitedUser)
        ->get(route('dashboard', $invitedUser->currentTeam));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('dashboard')
        ->has('pendingInvitations', 1)
        ->where('pendingInvitations.0.code', $invitation->code)
        ->where('pendingInvitations.0.inviterName', 'Taylor Otwell')
        ->where('pendingInvitations.0.team.name', 'Laravel Team')
        ->where('pendingInvitations.0.team.slug', $team->slug)
        ->missing('pendingInvitations.0.teamName'),
    );
});

test('dashboard does not include accepted invitations', function () {
    $owner = User::factory()->create();
    $invitedUser = User::factory()->create(['email' => 'invited@example.com']);
    $team = Team::factory()->create();

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    TeamInvitation::factory()->accepted()->create([
        'team_id' => $team->id,
        'email' => 'invited@example.com',
        'invited_by' => $owner->id,
    ]);

    $response = $this
        ->actingAs($invitedUser)
        ->get(route('dashboard', $invitedUser->currentTeam));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('dashboard')
        ->has('pendingInvitations', 0),
    );
});

test('dashboard excludes expired invitations without deleting them', function () {
    $owner = User::factory()->create();
    $invitedUser = User::factory()->create(['email' => 'invited@example.com']);
    $team = Team::factory()->create();

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $invitation = TeamInvitation::factory()->expired()->create([
        'team_id' => $team->id,
        'email' => 'invited@example.com',
        'invited_by' => $owner->id,
    ]);

    $response = $this
        ->actingAs($invitedUser)
        ->get(route('dashboard', $invitedUser->currentTeam));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('dashboard')
        ->has('pendingInvitations', 0),
    );

    $this->assertDatabaseHas('team_invitations', [
        'id' => $invitation->id,
    ]);
});

test('dashboard does not include or delete other users invitations', function () {
    $owner = User::factory()->create();
    $invitedUser = User::factory()->create(['email' => 'invited@example.com']);
    $team = Team::factory()->create();

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $invitation = TeamInvitation::factory()->expired()->create([
        'team_id' => $team->id,
        'email' => 'someone@example.com',
        'invited_by' => $owner->id,
    ]);

    $response = $this
        ->actingAs($invitedUser)
        ->get(route('dashboard', $invitedUser->currentTeam));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('dashboard')
        ->has('pendingInvitations', 0),
    );

    $this->assertDatabaseHas('team_invitations', [
        'id' => $invitation->id,
    ]);
});
