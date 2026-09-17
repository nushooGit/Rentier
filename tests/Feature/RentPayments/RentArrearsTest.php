<?php

use App\Models\Expense;
use App\Models\Lease;
use App\Models\RentPayment;
use App\Models\User;
use App\Services\LeaseRentStatusCalculator;
use App\Services\RentPaymentAllocationCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

function pay04Lease(array $overrides = []): Lease
{
    $user = User::factory()->create();

    return Lease::factory()->for($user->currentTeam)->create(array_merge([
        'start_date' => '2026-07-01',
        'end_date' => '2026-12-31',
        'monthly_rent_amount' => 2500,
        'rent_due_day' => 5,
        'deposit_amount' => 0,
    ], $overrides));
}

function pay04RentPayment(Lease $lease, int|float $amount, array $overrides = []): RentPayment
{
    return RentPayment::factory()->for($lease->team)->create(array_merge([
        'lease_id' => $lease->id,
        'property_id' => $lease->property_id,
        'renter_id' => $lease->renter_id,
        'amount' => $amount,
        'payment_type' => 'rent',
        'period_month' => 7,
        'period_year' => 2026,
        'payment_date' => '2026-07-05',
        'status' => 'paid',
    ], $overrides));
}

function pay04Status(Lease $lease, string $date): array
{
    return app(LeaseRentStatusCalculator::class)->forLease($lease, Carbon::parse($date));
}

test('PAY-04 production regression aggregates august partial and september overdue rent', function () {
    Carbon::setTestNow('2026-09-17');

    $lease = pay04Lease();
    $user = $lease->team->owner();
    pay04RentPayment($lease, 4000);

    $status = pay04Status($lease, '2026-09-17');

    expect($status['remaining_amount'])->toBe('2500.00')
        ->and($status['arrears_amount'])->toBe('3500.00')
        ->and($status['overdue_month_count'])->toBe(2)
        ->and($status['oldest_overdue_due_date'])->toBe('2026-08-05')
        ->and($status['overdue_months'][0]['period_key'])->toBe('2026-08')
        ->and($status['overdue_months'][0]['remaining_amount'])->toBe('1000.00')
        ->and($status['overdue_months'][1]['period_key'])->toBe('2026-09')
        ->and($status['overdue_months'][1]['remaining_amount'])->toBe('2500.00');

    $this
        ->actingAs($user)
        ->get(route('dashboard', $lease->team))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary.remaining_rent', '2500.00')
            ->where('summary.overdue_rent', '3500.00')
            ->where('summary.total_receivable', '3500.00')
            ->where('summary.overdue_month_count', 2)
            ->where('overdueLeases.0.arrears_amount', '3500.00')
            ->where('overdueLeases.0.overdue_months.0.period_key', '2026-08')
            ->where('overdueLeases.0.overdue_months.1.period_key', '2026-09')
        );

    Carbon::setTestNow();
});

test('PAY-04 excludes the current month before its due date while retaining previous arrears', function () {
    $lease = pay04Lease();
    pay04RentPayment($lease, 4000);

    $status = pay04Status($lease, '2026-09-03');

    expect($status['arrears_amount'])->toBe('1000.00')
        ->and($status['overdue_month_count'])->toBe(1)
        ->and($status['overdue_months'][0]['period_key'])->toBe('2026-08')
        ->and($status['remaining_amount'])->toBe('2500.00')
        ->and($status['key'])->toBe('partial_overdue');
});

test('PAY-04 preserves partial and overdue status for the current month', function () {
    $lease = pay04Lease(['start_date' => '2026-09-01']);
    pay04RentPayment($lease, 1000, [
        'period_month' => 9,
        'period_year' => 2026,
        'payment_date' => '2026-09-04',
    ]);

    $status = pay04Status($lease, '2026-09-17');

    expect($status['key'])->toBe('partial_overdue')
        ->and($status['arrears_amount'])->toBe('1500.00')
        ->and($status['overdue_month_count'])->toBe(1)
        ->and($status['badges'][0]['key'])->toBe('partial')
        ->and($status['badges'][1]['key'])->toBe('arrears');
});

test('PAY-04 aggregates multiple completely unpaid months', function () {
    $lease = pay04Lease();

    $status = pay04Status($lease, '2026-09-17');

    expect($status['arrears_amount'])->toBe('7500.00')
        ->and($status['overdue_month_count'])->toBe(3)
        ->and(array_column($status['overdue_months'], 'period_key'))
        ->toBe(['2026-07', '2026-08', '2026-09']);
});

test('PAY-04 advance allocation creates no false arrears', function () {
    $lease = pay04Lease();
    pay04RentPayment($lease, 5000);

    $status = pay04Status($lease, '2026-07-10');

    expect($status['arrears_amount'])->toBe('0.00')
        ->and($status['overdue_month_count'])->toBe(0)
        ->and($status['advance_months'][0]['period_key'])->toBe('2026-08');
});

test('PAY-04 fully caught up lease has zero arrears', function () {
    $lease = pay04Lease();
    pay04RentPayment($lease, 7500);

    $status = pay04Status($lease, '2026-09-17');

    expect($status['arrears_amount'])->toBe('0.00')
        ->and($status['overdue_month_count'])->toBe(0)
        ->and($status['key'])->toBe('paid');
});

test('PAY-04 creates no obligations before the lease start', function () {
    $lease = pay04Lease(['start_date' => '2026-08-15']);

    $allocation = app(RentPaymentAllocationCalculator::class)
        ->forLease($lease, Carbon::parse('2026-09-17'));
    $status = pay04Status($lease, '2026-09-17');

    expect($allocation['months'])->not->toHaveKey('2026-07')
        ->and($status['arrears_amount'])->toBe('5000.00')
        ->and(array_column($status['overdue_months'], 'period_key'))
        ->toBe(['2026-08', '2026-09']);
});

test('PAY-04 creates no obligations after the lease end', function () {
    $lease = pay04Lease(['end_date' => '2026-08-31']);

    $allocation = app(RentPaymentAllocationCalculator::class)
        ->forLease($lease, Carbon::parse('2026-09-17'));
    $status = pay04Status($lease, '2026-09-17');

    expect($allocation['months'])->not->toHaveKey('2026-09')
        ->and($status['remaining_amount'])->toBe('0.00')
        ->and($status['arrears_amount'])->toBe('5000.00')
        ->and($status['overdue_month_count'])->toBe(2);
});

test('PAY-04 rent deductions reduce the corresponding overdue obligation', function () {
    $lease = pay04Lease(['start_date' => '2026-08-01']);

    Expense::factory()->for($lease->team)->create([
        'property_id' => $lease->property_id,
        'lease_id' => $lease->id,
        'amount' => 1000,
        'expense_date' => '2026-08-03',
        'paid_by' => 'tenant',
        'responsible_party' => 'owner',
        'settlement_type' => 'deduct_from_rent',
        'status' => 'paid',
    ]);

    $status = pay04Status($lease, '2026-09-17');

    expect($status['arrears_amount'])->toBe('4000.00')
        ->and($status['overdue_months'][0]['period_key'])->toBe('2026-08')
        ->and($status['overdue_months'][0]['rent_deduction_amount'])->toBe('1000.00')
        ->and($status['overdue_months'][0]['remaining_amount'])->toBe('1500.00');
});

test('PAY-04 guarantee payments do not reduce rent arrears', function () {
    $lease = pay04Lease(['start_date' => '2026-09-01', 'deposit_amount' => 2500]);
    pay04RentPayment($lease, 2500, [
        'payment_type' => 'guarantee',
        'period_month' => null,
        'period_year' => null,
        'payment_date' => '2026-09-04',
    ]);

    $status = pay04Status($lease, '2026-09-17');

    expect($status['arrears_amount'])->toBe('2500.00')
        ->and($status['overdue_month_count'])->toBe(1)
        ->and($status['collected_amount'])->toBe('0.00');
});

test('PAY-04 total receivable combines arrears guarantees and recoverables once', function () {
    Carbon::setTestNow('2026-09-17');

    $lease = pay04Lease(['deposit_amount' => 1000]);
    $user = $lease->team->owner();
    pay04RentPayment($lease, 4000);
    pay04RentPayment($lease, 400, [
        'payment_type' => 'guarantee',
        'period_month' => null,
        'period_year' => null,
        'payment_date' => '2026-09-10',
    ]);
    Expense::factory()->for($lease->team)->create([
        'property_id' => $lease->property_id,
        'lease_id' => $lease->id,
        'amount' => 700,
        'expense_date' => '2026-09-10',
        'paid_by' => 'owner',
        'responsible_party' => 'tenant',
        'settlement_type' => 'reimburse',
        'settled_at' => null,
        'status' => 'reimbursable',
    ]);

    $this
        ->actingAs($user)
        ->get(route('dashboard', $lease->team))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary.overdue_rent', '3500.00')
            ->where('summary.remaining_guarantees', '600.00')
            ->where('summary.recoverable_expenses', '700.00')
            ->where('summary.total_receivable', '4800.00')
        );

    Carbon::setTestNow();
});

test('PAY-04 property card exposes older arrears and overdue month count', function () {
    Carbon::setTestNow('2026-09-17');

    $lease = pay04Lease();
    $user = $lease->team->owner();
    pay04RentPayment($lease, 4000);

    $this
        ->actingAs($user)
        ->get(route('properties.index', $lease->team))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('properties.0.rent_payment_status.arrears_amount', '3500.00')
            ->where('properties.0.rent_payment_status.overdue_month_count', 2)
            ->where('properties.0.rent_payment_status.oldest_overdue_due_date', '2026-08-05')
            ->where('properties.0.rent_payment_status.badges.1.label', 'Restanță: 3.500 RON')
            ->where('properties.0.rent_payment_status.badges.2.label', '2 luni restante')
            ->where('properties.0.rent_payment_status.overdue_months.0.period_key', '2026-08')
        );

    Carbon::setTestNow();
});
