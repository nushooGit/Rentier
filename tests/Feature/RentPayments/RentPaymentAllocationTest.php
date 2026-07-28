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

function pay03Lease(array $overrides = []): Lease
{
    $user = User::factory()->create();

    return Lease::factory()->for($user->currentTeam)->create(array_merge([
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'monthly_rent_amount' => 2500,
        'rent_due_day' => 5,
    ], $overrides));
}

function pay03Payment(Lease $lease, int|float $amount, array $overrides = []): RentPayment
{
    return RentPayment::factory()->for($lease->team)->create(array_merge([
        'lease_id' => $lease->id,
        'property_id' => $lease->property_id,
        'renter_id' => $lease->renter_id,
        'amount' => $amount,
        'payment_type' => 'rent',
        'method' => 'bank_transfer',
        'period_month' => 7,
        'period_year' => 2026,
        'payment_date' => '2026-07-05',
        'status' => 'paid',
    ], $overrides));
}

function pay03Expense(Lease $lease, int|float $amount, array $overrides = []): Expense
{
    return Expense::factory()->for($lease->team)->create(array_merge([
        'property_id' => $lease->property_id,
        'lease_id' => $lease->id,
        'amount' => $amount,
        'expense_date' => '2026-07-04',
        'paid_by' => 'tenant',
        'responsible_party' => 'owner',
        'settlement_type' => 'deduct_from_rent',
        'status' => 'paid',
    ], $overrides));
}

function pay03Allocation(Lease $lease): array
{
    return app(RentPaymentAllocationCalculator::class)->forLease($lease, Carbon::parse('2026-07-10'));
}

test('5000 rent payment covers current month and next month fully', function () {
    $lease = pay03Lease();
    pay03Payment($lease, 5000);

    $months = pay03Allocation($lease)['months'];

    expect($months['2026-07']['cash_allocated'])->toBe('2500.00')
        ->and($months['2026-07']['fully_paid'])->toBeTrue()
        ->and($months['2026-08']['cash_allocated'])->toBe('2500.00')
        ->and($months['2026-08']['fully_paid'])->toBeTrue();
});

test('6000 rent payment covers two months and partially covers the third', function () {
    $lease = pay03Lease();
    pay03Payment($lease, 6000);

    $months = pay03Allocation($lease)['months'];

    expect($months['2026-07']['cash_allocated'])->toBe('2500.00')
        ->and($months['2026-08']['cash_allocated'])->toBe('2500.00')
        ->and($months['2026-09']['cash_allocated'])->toBe('1000.00')
        ->and($months['2026-09']['remaining_amount'])->toBe('1500.00')
        ->and($months['2026-09']['partial'])->toBeTrue();
});

test('two rent payments combine across months', function () {
    $lease = pay03Lease();
    pay03Payment($lease, 1000, ['payment_date' => '2026-07-01']);
    pay03Payment($lease, 2000, ['payment_date' => '2026-07-02']);

    $months = pay03Allocation($lease)['months'];

    expect($months['2026-07']['cash_allocated'])->toBe('2500.00')
        ->and($months['2026-07']['fully_paid'])->toBeTrue()
        ->and($months['2026-08']['cash_allocated'])->toBe('500.00')
        ->and($months['2026-08']['partial'])->toBeTrue();
});

test('three separate july payments allocate july fully and august fully in advance', function () {
    $lease = pay03Lease([
        'start_date' => '2026-07-01',
        'end_date' => '2026-08-31',
    ]);
    $first = pay03Payment($lease, 1500, ['payment_date' => '2026-07-01']);
    $second = pay03Payment($lease, 1000, ['payment_date' => '2026-07-02']);
    $third = pay03Payment($lease, 2500, ['payment_date' => '2026-07-03']);

    $allocation = pay03Allocation($lease);

    expect($allocation['months']['2026-07']['cash_allocated'])->toBe('2500.00')
        ->and($allocation['months']['2026-07']['fully_paid'])->toBeTrue()
        ->and($allocation['months']['2026-08']['cash_allocated'])->toBe('2500.00')
        ->and($allocation['months']['2026-08']['fully_paid'])->toBeTrue()
        ->and($allocation['payments'][$first->id]['breakdown'])->toHaveCount(1)
        ->and($allocation['payments'][$first->id]['breakdown'][0]['period_key'])->toBe('2026-07')
        ->and($allocation['payments'][$first->id]['breakdown'][0]['amount'])->toBe('1500.00')
        ->and($allocation['payments'][$second->id]['breakdown'])->toHaveCount(1)
        ->and($allocation['payments'][$second->id]['breakdown'][0]['period_key'])->toBe('2026-07')
        ->and($allocation['payments'][$second->id]['breakdown'][0]['amount'])->toBe('1000.00')
        ->and($allocation['payments'][$third->id]['breakdown'])->toHaveCount(1)
        ->and($allocation['payments'][$third->id]['breakdown'][0]['period_key'])->toBe('2026-08')
        ->and($allocation['payments'][$third->id]['breakdown'][0]['amount'])->toBe('2500.00');
});

test('editing a payment recalculates allocation', function () {
    $lease = pay03Lease();
    $payment = pay03Payment($lease, 5000);

    $payment->update(['amount' => 3000]);

    $months = pay03Allocation($lease)['months'];

    expect($months['2026-07']['cash_allocated'])->toBe('2500.00')
        ->and($months['2026-08']['cash_allocated'])->toBe('500.00');
});

test('deleting a payment recalculates allocation', function () {
    $lease = pay03Lease();
    $payment = pay03Payment($lease, 5000);

    $payment->delete();

    $months = pay03Allocation($lease)['months'];

    expect($months['2026-07']['cash_allocated'])->toBe('0.00')
        ->and($months)->not->toHaveKey('2026-08');
});

test('future selected payment never covers an earlier month', function () {
    $lease = pay03Lease();
    pay03Payment($lease, 2500, [
        'period_month' => 8,
        'period_year' => 2026,
    ]);

    $months = pay03Allocation($lease)['months'];

    expect($months['2026-07']['cash_allocated'])->toBe('0.00')
        ->and($months['2026-08']['cash_allocated'])->toBe('2500.00');
});

test('allocation respects lease start date', function () {
    $lease = pay03Lease(['start_date' => '2026-08-15']);
    pay03Payment($lease, 2500, [
        'period_month' => 7,
        'period_year' => 2026,
    ]);

    $months = pay03Allocation($lease)['months'];

    expect($months)->not->toHaveKey('2026-07')
        ->and($months['2026-08']['cash_allocated'])->toBe('2500.00');
});

test('allocation respects lease end date', function () {
    $lease = pay03Lease(['end_date' => '2026-08-20']);
    pay03Payment($lease, 7500);

    $allocation = pay03Allocation($lease);

    expect($allocation['months'])->toHaveKeys(['2026-07', '2026-08'])
        ->and($allocation['payments'][RentPayment::first()->id]['unallocated_amount'])->toBe('2500.00');
});

test('excess beyond lease end becomes unallocated credit', function () {
    $lease = pay03Lease([
        'start_date' => '2026-07-01',
        'end_date' => '2026-07-31',
    ]);
    $payment = pay03Payment($lease, 4000);

    $allocation = pay03Allocation($lease);

    expect($allocation['months']['2026-07']['cash_allocated'])->toBe('2500.00')
        ->and($allocation['payments'][$payment->id]['unallocated_amount'])->toBe('1500.00');
});

test('open-ended lease allocation remains finite', function () {
    $lease = pay03Lease(['end_date' => null]);
    pay03Payment($lease, 6000);

    $months = pay03Allocation($lease)['months'];

    expect($months)->toHaveKeys(['2026-07', '2026-08', '2026-09'])
        ->and($months)->not->toHaveKey('2026-10')
        ->and($months['2026-09']['cash_allocated'])->toBe('1000.00');
});

test('guarantee payment creates no rent allocation', function () {
    $lease = pay03Lease();
    $payment = pay03Payment($lease, 2500, [
        'payment_type' => 'guarantee',
        'period_month' => null,
        'period_year' => null,
    ]);

    $allocation = app(RentPaymentAllocationCalculator::class)->paymentAllocation($payment);
    $months = pay03Allocation($lease)['months'];

    expect($allocation)->toBeNull()
        ->and($months['2026-07']['cash_allocated'])->toBe('0.00');
});

test('rent deduction reduces cash required and rolls cash forward', function () {
    $lease = pay03Lease();
    pay03Expense($lease, 500);
    pay03Payment($lease, 2500);

    $months = pay03Allocation($lease)['months'];

    expect($months['2026-07']['rent_deduction_amount'])->toBe('500.00')
        ->and($months['2026-07']['cash_allocated'])->toBe('2000.00')
        ->and($months['2026-08']['cash_allocated'])->toBe('500.00');
});

test('utility deduction does not reduce rent obligation', function () {
    $lease = pay03Lease();
    pay03Expense($lease, 500, ['settlement_type' => 'deduct_from_utilities']);
    pay03Payment($lease, 2500);

    $months = pay03Allocation($lease)['months'];

    expect($months['2026-07']['rent_deduction_amount'])->toBe('0.00')
        ->and($months['2026-07']['cash_allocated'])->toBe('2500.00')
        ->and($months)->not->toHaveKey('2026-08');
});

test('reimbursement expense does not reduce rent obligation', function () {
    $lease = pay03Lease();
    pay03Expense($lease, 500, ['settlement_type' => 'reimburse']);
    pay03Payment($lease, 2500);

    $months = pay03Allocation($lease)['months'];

    expect($months['2026-07']['rent_deduction_amount'])->toBe('0.00')
        ->and($months['2026-07']['cash_allocated'])->toBe('2500.00')
        ->and($months)->not->toHaveKey('2026-08');
});

test('fully paid future month produces advance UI data', function () {
    $lease = pay03Lease();
    pay03Payment($lease, 5000);

    $status = app(LeaseRentStatusCalculator::class)->forLease($lease, Carbon::parse('2026-07-10'));

    expect($status['advance_notice']['key'])->toBe('paid_through')
        ->and($status['advance_months'][0]['period_key'])->toBe('2026-08')
        ->and($status['advance_months'][0]['fully_paid'])->toBeTrue();
});

test('partial future month produces partial advance UI data', function () {
    $lease = pay03Lease();
    pay03Payment($lease, 3000);

    $status = app(LeaseRentStatusCalculator::class)->forLease($lease, Carbon::parse('2026-07-10'));

    expect($status['advance_notice']['key'])->toBe('partial_advance')
        ->and($status['advance_months'][0]['period_key'])->toBe('2026-08')
        ->and($status['advance_months'][0]['total_covered'])->toBe('500.00');
});

test('full and partial future advance notices are exposed together', function () {
    Carbon::setTestNow('2026-07-10');

    $lease = pay03Lease([
        'start_date' => '2026-07-01',
        'end_date' => '2026-09-30',
    ]);
    $user = $lease->team->owner();
    pay03Payment($lease, 6000);

    $status = app(LeaseRentStatusCalculator::class)->forLease($lease, Carbon::parse('2026-07-10'));

    expect($status['advance_notice']['key'])->toBe('paid_through')
        ->and($status['advance_notices'])->toHaveCount(2)
        ->and($status['advance_notices'][0]['key'])->toBe('paid_through')
        ->and($status['advance_notices'][0]['period_key'])->toBe('2026-08')
        ->and($status['advance_notices'][0]['label'])->toBe('Plătită în avans până în august 2026')
        ->and($status['advance_notices'][1]['key'])->toBe('partial_advance')
        ->and($status['advance_notices'][1]['period_key'])->toBe('2026-09')
        ->and($status['advance_notices'][1]['label'])->toBe('Avans pentru septembrie 2026: 1.000 RON / 2.500 RON');

    $this
        ->actingAs($user)
        ->get(route('properties.index', $lease->team))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('properties.0.rent_payment_status.advance_notices.0.label', 'Plătită în avans până în august 2026')
            ->where('properties.0.rent_payment_status.advance_notices.1.label', 'Avans pentru septembrie 2026: 1.000 RON / 2.500 RON')
        );

    $this
        ->actingAs($user)
        ->get(route('dashboard', $lease->team))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary.current_month_payments', '6000.00')
            ->where('advanceLeases.0.advance_notices.0.label', 'Plătită în avans până în august 2026')
            ->where('advanceLeases.0.advance_notices.1.label', 'Avans pentru septembrie 2026: 1.000 RON / 2.500 RON')
        );

    Carbon::setTestNow();
});

test('current partial overdue behavior remains correct', function () {
    $lease = pay03Lease(['rent_due_day' => 5]);
    pay03Payment($lease, 1000);

    $status = app(LeaseRentStatusCalculator::class)->forLease($lease, Carbon::parse('2026-07-10'));

    expect($status['key'])->toBe('partial_overdue')
        ->and($status['days'])->toBe(5)
        ->and($status['remaining_amount'])->toBe('1500.00');
});

test('dashboard cash totals are not multiplied by allocation months', function () {
    Carbon::setTestNow('2026-07-10');

    $lease = pay03Lease();
    $user = $lease->team->owner();
    pay03Payment($lease, 5000);

    $this
        ->actingAs($user)
        ->get(route('dashboard', $lease->team))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary.current_month_payments', '5000.00')
            ->where('summary.current_month_covered_rent', '2500.00')
            ->where('rentPaymentMethodBreakdown.0.amount', '5000.00')
        );

    Carbon::setTestNow();
});

test('property response includes advance notice for multiple july payments', function () {
    Carbon::setTestNow('2026-07-10');

    $lease = pay03Lease([
        'start_date' => '2026-07-01',
        'end_date' => '2026-08-31',
    ]);
    $user = $lease->team->owner();
    pay03Payment($lease, 1500, ['payment_date' => '2026-07-01']);
    pay03Payment($lease, 1000, ['payment_date' => '2026-07-02']);
    pay03Payment($lease, 2500, ['payment_date' => '2026-07-03']);

    $this
        ->actingAs($user)
        ->get(route('properties.index', $lease->team))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('properties.0.id', $lease->property_id)
            ->where('properties.0.rent_payment_status.advance_notice.key', 'paid_through')
            ->where('properties.0.rent_payment_status.advance_notice.period_key', '2026-08')
            ->where('properties.0.rent_payment_status.advance_notice.label', 'Plătită în avans până în august 2026')
        );

    Carbon::setTestNow();
});

test('dashboard response includes advance notice and exact cash for multiple july payments', function () {
    Carbon::setTestNow('2026-07-10');

    $lease = pay03Lease([
        'start_date' => '2026-07-01',
        'end_date' => '2026-08-31',
    ]);
    $user = $lease->team->owner();
    pay03Payment($lease, 1500, ['payment_date' => '2026-07-01']);
    pay03Payment($lease, 1000, ['payment_date' => '2026-07-02']);
    pay03Payment($lease, 2500, ['payment_date' => '2026-07-03']);

    $this
        ->actingAs($user)
        ->get(route('dashboard', $lease->team))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary.current_month_payments', '5000.00')
            ->where('summary.current_month_covered_rent', '2500.00')
            ->where('rentPaymentMethodBreakdown.0.amount', '5000.00')
            ->where('advanceLeases.0.lease_id', $lease->id)
            ->where('advanceLeases.0.advance_notice.key', 'paid_through')
            ->where('advanceLeases.0.advance_notice.period_key', '2026-08')
            ->where('advanceLeases.0.advance_notice.label', 'Plătită în avans până în august 2026')
            ->where('upcomingPayments', [])
            ->where('overdueLeases', [])
        );

    Carbon::setTestNow();
});

test('payment index exposes allocation breakdown and individual payment statuses', function () {
    Carbon::setTestNow('2026-07-10');

    $lease = pay03Lease([
        'start_date' => '2026-07-01',
        'end_date' => '2026-08-31',
    ]);
    $user = $lease->team->owner();
    $first = pay03Payment($lease, 1500, ['payment_date' => '2026-07-01']);
    $second = pay03Payment($lease, 1000, ['payment_date' => '2026-07-02']);
    $third = pay03Payment($lease, 2500, ['payment_date' => '2026-07-03']);

    $this
        ->actingAs($user)
        ->get(route('payments.index', $lease->team))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('payments.0.id', $third->id)
            ->where('payments.0.status_summary.status_key', 'paid')
            ->where('payments.0.allocation_summary.breakdown.0.period_key', '2026-08')
            ->where('payments.0.allocation_summary.breakdown.0.amount', '2500.00')
            ->where('payments.1.id', $second->id)
            ->where('payments.1.status_summary.status_key', 'partial')
            ->where('payments.1.allocation_summary.breakdown.0.period_key', '2026-07')
            ->where('payments.1.allocation_summary.breakdown.0.amount', '1000.00')
            ->where('payments.2.id', $first->id)
            ->where('payments.2.status_summary.status_key', 'partial')
            ->where('payments.2.allocation_summary.breakdown.0.period_key', '2026-07')
            ->where('payments.2.allocation_summary.breakdown.0.amount', '1500.00')
        );

    Carbon::setTestNow();
});

test('payment show exposes allocation breakdown for the selected transaction', function () {
    Carbon::setTestNow('2026-07-10');

    $lease = pay03Lease([
        'start_date' => '2026-07-01',
        'end_date' => '2026-08-31',
    ]);
    $user = $lease->team->owner();
    pay03Payment($lease, 1500, ['payment_date' => '2026-07-01']);
    pay03Payment($lease, 1000, ['payment_date' => '2026-07-02']);
    $third = pay03Payment($lease, 2500, ['payment_date' => '2026-07-03']);

    $this
        ->actingAs($user)
        ->get(route('payments.show', [$lease->team, $third]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('payment.id', $third->id)
            ->where('payment.status_summary.status_key', 'paid')
            ->where('payment.allocation_summary.breakdown.0.period_key', '2026-08')
            ->where('payment.allocation_summary.breakdown.0.amount', '2500.00')
        );

    Carbon::setTestNow();
});
