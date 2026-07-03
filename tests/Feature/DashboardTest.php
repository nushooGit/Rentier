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
        ->where('summary.current_month_profit', '1600.00')
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
