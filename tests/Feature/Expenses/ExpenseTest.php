<?php

use App\Models\Expense;
use App\Models\Lease;
use App\Models\Property;
use App\Models\Team;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

function validExpensePayload(Property $property, array $overrides = []): array
{
    return array_merge([
        'property_id' => $property->id,
        'lease_id' => null,
        'title' => 'Reparație baie',
        'category' => 'repairs',
        'amount' => 650,
        'currency' => 'RON',
        'expense_date' => '2026-06-12',
        'paid_by' => 'owner',
        'responsible_party' => 'owner',
        'settlement_type' => 'none',
        'status' => 'paid',
        'notes' => 'Schimbat racorduri.',
    ], $overrides);
}

test('expenses index shows expenses for the current workspace', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $otherTeam = Team::factory()->create();

    Expense::factory()->for($team)->create();
    Expense::factory()->for($otherTeam)->create();

    $response = $this
        ->actingAs($user)
        ->get(route('expenses.index', $team));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('expenses/index')
            ->has('expenses', 1)
        );
});

test('workspace members can create expenses', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $property = Property::factory()->for($team)->create();
    $lease = Lease::factory()->for($team)->create([
        'property_id' => $property->id,
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
    ]);

    $response = $this
        ->actingAs($user)
        ->post(route('expenses.store', $team), validExpensePayload($property, [
            'lease_id' => $lease->id,
        ]));

    $expense = Expense::first();

    $response->assertRedirect(route('expenses.index', $team));

    $this->assertDatabaseHas('expenses', [
        'team_id' => $team->id,
        'property_id' => $property->id,
        'lease_id' => $lease->id,
        'title' => 'Reparație baie',
        'amount' => 650,
    ]);
});

test('expense validation requires core fields and same property lease', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $property = Property::factory()->for($team)->create();
    $otherProperty = Property::factory()->for($team)->create();
    $lease = Lease::factory()->for($team)->create([
        'property_id' => $otherProperty->id,
    ]);

    $response = $this
        ->actingAs($user)
        ->post(route('expenses.store', $team), validExpensePayload($property, [
            'lease_id' => $lease->id,
            'title' => '',
            'category' => 'unknown',
            'amount' => -1,
            'currency' => '',
            'expense_date' => '',
            'paid_by' => 'nobody',
            'responsible_party' => 'nobody',
            'settlement_type' => 'unknown',
        ]));

    $response->assertSessionHasErrors([
        'lease_id',
        'title',
        'category',
        'amount',
        'currency',
        'expense_date',
        'paid_by',
        'responsible_party',
        'settlement_type',
    ]);
});

test('expense category is required on create and update', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $property = Property::factory()->for($team)->create();
    $expense = Expense::factory()->for($team)->create([
        'property_id' => $property->id,
        'category' => 'repairs',
    ]);

    $this
        ->actingAs($user)
        ->post(route('expenses.store', $team), validExpensePayload($property, [
            'category' => '',
        ]))
        ->assertSessionHasErrors(['category']);

    $this
        ->actingAs($user)
        ->patch(route('expenses.update', [$team, $expense]), validExpensePayload($property, [
            'category' => '',
        ]))
        ->assertSessionHasErrors(['category']);
});

test('expenses without an explicit category default safely to other', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $property = Property::factory()->for($team)->create();

    $expense = Expense::create([
        'team_id' => $team->id,
        'property_id' => $property->id,
        'title' => 'Cheltuiala veche',
        'amount' => 100,
        'currency' => 'RON',
        'expense_date' => '2026-07-10',
        'paid_by' => 'owner',
        'responsible_party' => 'owner',
        'settlement_type' => 'none',
        'status' => 'paid',
    ]);

    expect($expense->category)->toBe('other');

    $this
        ->actingAs($user)
        ->get(route('expenses.index', $team))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('expenses.0.category', 'other')
            ->where('summary.by_category.other', '100.00')
        );
});

test('expenses default settlement fields to owner responsibility without settlement', function () {
    $team = Team::factory()->create();
    $property = Property::factory()->for($team)->create();

    $expense = Expense::create([
        'team_id' => $team->id,
        'property_id' => $property->id,
        'title' => 'Reparatie',
        'category' => 'repairs',
        'amount' => 100,
        'currency' => 'RON',
        'expense_date' => '2026-07-10',
        'status' => 'paid',
    ]);

    expect($expense->paid_by)->toBe('owner')
        ->and($expense->responsible_party)->toBe('owner')
        ->and($expense->settlement_type)->toBe('none');
});

test('expenses index can filter by repairs', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $property = Property::factory()->for($team)->create();

    Expense::factory()->for($team)->create([
        'property_id' => $property->id,
        'title' => 'Reparatie centrala',
        'category' => 'repairs',
    ]);
    Expense::factory()->for($team)->create([
        'property_id' => $property->id,
        'title' => 'Intretinere bloc',
        'category' => 'maintenance',
    ]);

    $this
        ->actingAs($user)
        ->get(route('expenses.index', [$team, 'category' => 'repairs']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.category', 'repairs')
            ->has('expenses', 1)
            ->where('expenses.0.category', 'repairs')
            ->where('expenses.0.title', 'Reparatie centrala')
        );
});

test('expenses index can filter by maintenance', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $property = Property::factory()->for($team)->create();

    Expense::factory()->for($team)->create([
        'property_id' => $property->id,
        'title' => 'Reparatie usa',
        'category' => 'repairs',
    ]);
    Expense::factory()->for($team)->create([
        'property_id' => $property->id,
        'title' => 'Intretinere luna iulie',
        'category' => 'maintenance',
    ]);

    $this
        ->actingAs($user)
        ->get(route('expenses.index', [$team, 'category' => 'maintenance']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.category', 'maintenance')
            ->has('expenses', 1)
            ->where('expenses.0.category', 'maintenance')
            ->where('expenses.0.title', 'Intretinere luna iulie')
        );
});

test('expense summary totals categories and separates paid from supported parties', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $lease = Lease::factory()->for($team)->create([
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
    ]);

    foreach ([
        ['category' => 'repairs', 'amount' => 100, 'paid_by' => 'owner', 'responsible_party' => 'owner', 'settlement_type' => 'none'],
        ['category' => 'maintenance', 'amount' => 50, 'paid_by' => 'tenant', 'responsible_party' => 'tenant', 'settlement_type' => 'none'],
        ['category' => 'utilities', 'amount' => 70, 'paid_by' => 'tenant', 'responsible_party' => 'tenant', 'settlement_type' => 'none'],
        ['category' => 'renovation', 'amount' => 30, 'paid_by' => 'owner', 'responsible_party' => 'tenant', 'settlement_type' => 'reimburse'],
        ['category' => 'taxes', 'amount' => 20, 'paid_by' => 'tenant', 'responsible_party' => 'owner', 'settlement_type' => 'reimburse'],
    ] as $expense) {
        Expense::factory()->for($team)->create([
            ...$expense,
            'property_id' => $lease->property_id,
            'lease_id' => $lease->id,
            'expense_date' => '2026-07-10',
            'status' => $expense['settlement_type'] === 'reimburse' ? 'reimbursable' : 'paid',
        ]);
    }

    $this
        ->actingAs($user)
        ->get(route('expenses.index', $team))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary.total', '270.00')
            ->where('summary.owner_supported', '120.00')
            ->where('summary.tenant_supported', '150.00')
            ->where('summary.owner_paid', '130.00')
            ->where('summary.tenant_paid', '140.00')
            ->where('summary.by_category.repairs', '100.00')
            ->where('summary.by_category.maintenance', '50.00')
            ->where('summary.by_category.utilities', '70.00')
            ->where('summary.by_category.renovation', '30.00')
            ->where('summary.by_category.taxes', '20.00')
            ->where('summary.by_category.other', '0.00')
        );
});

test('tenant responsible utilities do not count as owner supported expenses', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $lease = Lease::factory()->for($team)->create([
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
    ]);

    Expense::factory()->for($team)->create([
        'property_id' => $lease->property_id,
        'lease_id' => $lease->id,
        'category' => 'utilities',
        'amount' => 70,
        'paid_by' => 'tenant',
        'responsible_party' => 'tenant',
        'settlement_type' => 'none',
        'status' => 'paid',
    ]);

    $this
        ->actingAs($user)
        ->get(route('expenses.index', $team))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary.owner_supported', '0.00')
            ->where('summary.tenant_supported', '70.00')
        );
});

test('owner responsible repairs count as owner supported expenses', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $property = Property::factory()->for($team)->create();

    Expense::factory()->for($team)->create([
        'property_id' => $property->id,
        'category' => 'repairs',
        'amount' => 120,
        'paid_by' => 'owner',
        'responsible_party' => 'owner',
        'settlement_type' => 'none',
        'status' => 'paid',
    ]);

    $this
        ->actingAs($user)
        ->get(route('expenses.index', $team))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary.owner_supported', '120.00')
            ->where('summary.tenant_supported', '0.00')
        );
});

test('expense validation rejects invalid settlement combinations', function (array $overrides) {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $property = Property::factory()->for($team)->create();

    $response = $this
        ->actingAs($user)
        ->post(route('expenses.store', $team), validExpensePayload($property, $overrides));

    $response->assertSessionHasErrors(['settlement_type']);
})->with([
    'tenant pays owner cost without settlement' => [[
        'paid_by' => 'tenant',
        'responsible_party' => 'owner',
        'settlement_type' => 'none',
    ]],
    'tenant cost has settlement' => [[
        'paid_by' => 'tenant',
        'responsible_party' => 'tenant',
        'settlement_type' => 'reimburse',
    ]],
    'owner cost has settlement' => [[
        'paid_by' => 'owner',
        'responsible_party' => 'owner',
        'settlement_type' => 'reimburse',
    ]],
    'tenant responsible owner paid without reimbursement' => [[
        'paid_by' => 'owner',
        'responsible_party' => 'tenant',
        'settlement_type' => 'none',
    ]],
]);

test('workspace members can update and delete expenses', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $property = Property::factory()->for($team)->create();
    $expense = Expense::factory()->for($team)->create([
        'property_id' => $property->id,
        'title' => 'Original',
    ]);

    $response = $this
        ->actingAs($user)
        ->patch(route('expenses.update', [$team, $expense]), validExpensePayload($property, [
            'title' => 'Actualizat',
            'status' => 'reimbursable',
        ]));

    $response->assertRedirect(route('expenses.show', [$team, $expense]));

    $this->assertDatabaseHas('expenses', [
        'id' => $expense->id,
        'title' => 'Actualizat',
        'status' => 'paid',
    ]);

    $this
        ->actingAs($user)
        ->delete(route('expenses.destroy', [$team, $expense]))
        ->assertRedirect(route('expenses.index', $team));

    $this->assertDatabaseMissing('expenses', [
        'id' => $expense->id,
    ]);
});

test('expense status is derived from settlement context', function (array $overrides, string $expectedStatus) {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $property = Property::factory()->for($team)->create();
    Lease::factory()->for($team)->create([
        'property_id' => $property->id,
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
    ]);

    $response = $this
        ->actingAs($user)
        ->post(route('expenses.store', $team), validExpensePayload($property, [
            ...$overrides,
            'status' => 'cancelled',
        ]));

    $response->assertRedirect(route('expenses.index', $team));

    expect(Expense::latest()->first()->status)->toBe($expectedStatus);
})->with([
    'owner pays owner expense' => [[
        'paid_by' => 'owner',
        'responsible_party' => 'owner',
        'settlement_type' => 'none',
    ], 'paid'],
    'tenant pays own expense' => [[
        'paid_by' => 'tenant',
        'responsible_party' => 'tenant',
        'settlement_type' => 'none',
    ], 'paid'],
    'tenant pays owner expense with rent deduction' => [[
        'paid_by' => 'tenant',
        'responsible_party' => 'owner',
        'settlement_type' => 'deduct_from_rent',
    ], 'paid'],
    'tenant pays owner expense with utility deduction' => [[
        'paid_by' => 'tenant',
        'responsible_party' => 'owner',
        'settlement_type' => 'deduct_from_utilities',
    ], 'paid'],
    'tenant pays owner expense with reimbursement' => [[
        'paid_by' => 'tenant',
        'responsible_party' => 'owner',
        'settlement_type' => 'reimburse',
    ], 'reimbursable'],
    'owner pays tenant expense' => [[
        'paid_by' => 'owner',
        'responsible_party' => 'tenant',
        'settlement_type' => 'reimburse',
    ], 'reimbursable'],
]);

test('expenses index exposes outstanding reimbursement action state', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $property = Property::factory()->for($team)->create();
    $lease = Lease::factory()->for($team)->create([
        'property_id' => $property->id,
        'start_date' => '2026-07-01',
        'end_date' => '2026-07-31',
    ]);

    Expense::factory()->for($team)->create([
        'property_id' => $property->id,
        'lease_id' => $lease->id,
        'title' => 'Reparatie priza',
        'amount' => 150,
        'paid_by' => 'tenant',
        'responsible_party' => 'owner',
        'settlement_type' => 'reimburse',
        'status' => 'reimbursable',
    ]);

    $this
        ->actingAs($user)
        ->get(route('expenses.index', $team))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('expenses.0.settlement_state.kind', 'reimbursement_due')
            ->where('expenses.0.settlement_state.label', 'De rambursat')
            ->where('expenses.0.settlement_state.action_label', 'Marchează ca rambursat')
        );
});

test('expense can be marked as reimbursed once and remains visible as settled', function () {
    Carbon::setTestNow('2026-07-04 10:00:00');

    $user = User::factory()->create();
    $team = $user->currentTeam;
    $property = Property::factory()->for($team)->create();
    $lease = Lease::factory()->for($team)->create([
        'property_id' => $property->id,
        'start_date' => '2026-07-01',
        'end_date' => '2026-07-31',
    ]);
    $expense = Expense::factory()->for($team)->create([
        'property_id' => $property->id,
        'lease_id' => $lease->id,
        'amount' => 150,
        'paid_by' => 'tenant',
        'responsible_party' => 'owner',
        'settlement_type' => 'reimburse',
        'status' => 'reimbursable',
    ]);

    $this
        ->actingAs($user)
        ->patch(route('expenses.mark-reimbursed', [$team, $expense]))
        ->assertRedirect();

    $expense->refresh();

    expect($expense->settled_at?->toDateString())->toBe('2026-07-04')
        ->and($expense->responsible_party)->toBe('owner');

    $this
        ->actingAs($user)
        ->get(route('expenses.index', $team))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('expenses', 1)
            ->where('expenses.0.settlement_state.kind', 'reimbursed')
            ->where('expenses.0.settlement_state.label', 'Rambursat')
            ->where('expenses.0.settlement_state.action_label', 'Anulează rambursarea')
            ->where('expenses.0.settlement_state.action_route', route('expenses.undo-reimbursed', [$team, $expense], false))
        );

    $this
        ->actingAs($user)
        ->patch(route('expenses.mark-reimbursed', [$team, $expense]))
        ->assertSessionHasErrors(['expense']);

    Carbon::setTestNow();
});

test('undo reimbursed sets expense back to outstanding reimbursement', function () {
    Carbon::setTestNow('2026-07-04 10:00:00');

    $user = User::factory()->create();
    $team = $user->currentTeam;
    $property = Property::factory()->for($team)->create();
    $lease = Lease::factory()->for($team)->create([
        'property_id' => $property->id,
        'start_date' => '2026-07-01',
        'end_date' => '2026-07-31',
    ]);
    $expense = Expense::factory()->for($team)->create([
        'property_id' => $property->id,
        'lease_id' => $lease->id,
        'amount' => 150,
        'paid_by' => 'tenant',
        'responsible_party' => 'owner',
        'settlement_type' => 'reimburse',
        'settled_at' => now(),
        'status' => 'paid',
    ]);

    $this
        ->actingAs($user)
        ->patch(route('expenses.undo-reimbursed', [$team, $expense]))
        ->assertRedirect();

    expect($expense->refresh()->settled_at)->toBeNull()
        ->and($expense->status)->toBe('reimbursable');

    $this
        ->actingAs($user)
        ->get(route('expenses.index', $team))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('expenses.0.settlement_state.kind', 'reimbursement_due')
            ->where('expenses.0.settlement_state.label', 'De rambursat')
            ->where('expenses.0.settlement_state.action_label', 'Marchează ca rambursat')
            ->where('expenses.0.settlement_state.settled_label', null)
        );

    Carbon::setTestNow();
});

test('undo reimbursed makes amount outstanding again on dashboard', function () {
    Carbon::setTestNow('2026-07-10');

    $user = User::factory()->create();
    $team = $user->currentTeam;
    $lease = Lease::factory()->for($team)->create([
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'monthly_rent_amount' => 0,
        'deposit_amount' => 0,
    ]);
    $expense = Expense::factory()->for($team)->create([
        'property_id' => $lease->property_id,
        'lease_id' => $lease->id,
        'amount' => 150,
        'expense_date' => '2026-07-04',
        'paid_by' => 'tenant',
        'responsible_party' => 'owner',
        'settlement_type' => 'reimburse',
        'settled_at' => now(),
        'status' => 'paid',
    ]);

    $this
        ->actingAs($user)
        ->get(route('dashboard', $team))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary.tenant_reimbursement_expenses', '0.00')
            ->where('summary.operational_cash_result', '-150.00')
            ->where('summary.current_month_payments', '0.00')
            ->where('summary.collected_guarantees', '0.00')
        );

    $this
        ->actingAs($user)
        ->patch(route('expenses.undo-reimbursed', [$team, $expense]))
        ->assertRedirect();

    $this
        ->actingAs($user)
        ->get(route('dashboard', $team))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary.tenant_reimbursement_expenses', '150.00')
            ->where('summary.operational_cash_result', '0.00')
            ->where('summary.current_month_payments', '0.00')
            ->where('summary.collected_guarantees', '0.00')
        );

    Carbon::setTestNow();
});

test('expenses index exposes outstanding recovery action state and closes it', function () {
    Carbon::setTestNow('2026-07-04 10:00:00');

    $user = User::factory()->create();
    $team = $user->currentTeam;
    $property = Property::factory()->for($team)->create();
    $lease = Lease::factory()->for($team)->create([
        'property_id' => $property->id,
        'start_date' => '2026-07-01',
        'end_date' => '2026-07-31',
    ]);
    $expense = Expense::factory()->for($team)->create([
        'property_id' => $property->id,
        'lease_id' => $lease->id,
        'title' => 'Dauna usa',
        'amount' => 120,
        'paid_by' => 'owner',
        'responsible_party' => 'tenant',
        'settlement_type' => 'reimburse',
        'status' => 'reimbursable',
    ]);

    $this
        ->actingAs($user)
        ->get(route('expenses.index', $team))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('expenses.0.settlement_state.kind', 'recovery_due')
            ->where('expenses.0.settlement_state.label', 'De recuperat')
            ->where('expenses.0.settlement_state.action_label', 'Marchează ca recuperat')
        );

    $this
        ->actingAs($user)
        ->patch(route('expenses.mark-recovered', [$team, $expense]))
        ->assertRedirect();

    $this
        ->actingAs($user)
        ->get(route('expenses.index', $team))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('expenses.0.settlement_state.kind', 'recovered')
            ->where('expenses.0.settlement_state.label', 'Recuperat')
            ->where('expenses.0.settlement_state.action_label', 'Anulează recuperarea')
            ->where('expenses.0.settlement_state.action_route', route('expenses.undo-recovered', [$team, $expense], false))
        );

    Carbon::setTestNow();
});

test('undo recovered sets expense back to outstanding recovery', function () {
    Carbon::setTestNow('2026-07-04 10:00:00');

    $user = User::factory()->create();
    $team = $user->currentTeam;
    $property = Property::factory()->for($team)->create();
    $lease = Lease::factory()->for($team)->create([
        'property_id' => $property->id,
        'start_date' => '2026-07-01',
        'end_date' => '2026-07-31',
    ]);
    $expense = Expense::factory()->for($team)->create([
        'property_id' => $property->id,
        'lease_id' => $lease->id,
        'amount' => 120,
        'paid_by' => 'owner',
        'responsible_party' => 'tenant',
        'settlement_type' => 'reimburse',
        'settled_at' => now(),
        'status' => 'paid',
    ]);

    $this
        ->actingAs($user)
        ->patch(route('expenses.undo-recovered', [$team, $expense]))
        ->assertRedirect();

    expect($expense->refresh()->settled_at)->toBeNull()
        ->and($expense->status)->toBe('reimbursable');

    $this
        ->actingAs($user)
        ->get(route('expenses.index', $team))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('expenses.0.settlement_state.kind', 'recovery_due')
            ->where('expenses.0.settlement_state.label', 'De recuperat')
            ->where('expenses.0.settlement_state.action_label', 'Marchează ca recuperat')
            ->where('expenses.0.settlement_state.settled_label', null)
        );

    Carbon::setTestNow();
});

test('undo recovered makes amount outstanding again on dashboard', function () {
    Carbon::setTestNow('2026-07-10');

    $user = User::factory()->create();
    $team = $user->currentTeam;
    $lease = Lease::factory()->for($team)->create([
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'monthly_rent_amount' => 0,
        'deposit_amount' => 0,
    ]);
    $expense = Expense::factory()->for($team)->create([
        'property_id' => $lease->property_id,
        'lease_id' => $lease->id,
        'amount' => 120,
        'expense_date' => '2026-07-04',
        'paid_by' => 'owner',
        'responsible_party' => 'tenant',
        'settlement_type' => 'reimburse',
        'settled_at' => now(),
        'status' => 'paid',
    ]);

    $this
        ->actingAs($user)
        ->get(route('dashboard', $team))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary.recoverable_expenses', '0.00')
            ->where('summary.operational_cash_result', '0.00')
            ->where('summary.current_month_payments', '0.00')
            ->where('summary.collected_guarantees', '0.00')
        );

    $this
        ->actingAs($user)
        ->patch(route('expenses.undo-recovered', [$team, $expense]))
        ->assertRedirect();

    $this
        ->actingAs($user)
        ->get(route('dashboard', $team))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary.recoverable_expenses', '120.00')
            ->where('summary.operational_cash_result', '-120.00')
            ->where('summary.current_month_payments', '0.00')
            ->where('summary.collected_guarantees', '0.00')
        );

    Carbon::setTestNow();
});

test('invalid settlement actions are rejected', function (array $overrides, string $routeName) {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $property = Property::factory()->for($team)->create();
    $expense = Expense::factory()->for($team)->create([
        'property_id' => $property->id,
        ...$overrides,
    ]);

    $this
        ->actingAs($user)
        ->patch(route($routeName, [$team, $expense]))
        ->assertSessionHasErrors(['expense']);

    expect($expense->refresh()->settled_at)->toBeNull();
})->with([
    'owner owner cannot be recovered' => [[
        'paid_by' => 'owner',
        'responsible_party' => 'owner',
        'settlement_type' => 'none',
    ], 'expenses.mark-recovered'],
    'owner owner cannot be reimbursed' => [[
        'paid_by' => 'owner',
        'responsible_party' => 'owner',
        'settlement_type' => 'none',
    ], 'expenses.mark-reimbursed'],
    'tenant tenant cannot be recovered' => [[
        'paid_by' => 'tenant',
        'responsible_party' => 'tenant',
        'settlement_type' => 'none',
    ], 'expenses.mark-recovered'],
    'tenant tenant cannot be reimbursed' => [[
        'paid_by' => 'tenant',
        'responsible_party' => 'tenant',
        'settlement_type' => 'none',
    ], 'expenses.mark-reimbursed'],
    'deduct from rent cannot be reimbursed' => [[
        'paid_by' => 'tenant',
        'responsible_party' => 'owner',
        'settlement_type' => 'deduct_from_rent',
    ], 'expenses.mark-reimbursed'],
    'deduct from rent cannot be recovered' => [[
        'paid_by' => 'tenant',
        'responsible_party' => 'owner',
        'settlement_type' => 'deduct_from_rent',
    ], 'expenses.mark-recovered'],
]);

test('invalid undo settlement actions are rejected', function (array $overrides, string $routeName, string $message) {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $property = Property::factory()->for($team)->create();
    $expense = Expense::factory()->for($team)->create([
        'property_id' => $property->id,
        ...$overrides,
    ]);

    $this
        ->actingAs($user)
        ->patch(route($routeName, [$team, $expense]))
        ->assertSessionHasErrors(['expense' => $message]);
})->with([
    'unsettled reimbursement cannot be undone' => [[
        'paid_by' => 'tenant',
        'responsible_party' => 'owner',
        'settlement_type' => 'reimburse',
        'settled_at' => null,
    ], 'expenses.undo-reimbursed', 'Această cheltuială nu este închisă.'],
    'unsettled recovery cannot be undone' => [[
        'paid_by' => 'owner',
        'responsible_party' => 'tenant',
        'settlement_type' => 'reimburse',
        'settled_at' => null,
    ], 'expenses.undo-recovered', 'Această cheltuială nu este închisă.'],
    'owner owner cannot undo reimbursement' => [[
        'paid_by' => 'owner',
        'responsible_party' => 'owner',
        'settlement_type' => 'none',
        'settled_at' => '2026-07-04 10:00:00',
    ], 'expenses.undo-reimbursed', 'Această acțiune nu este permisă pentru această cheltuială.'],
    'tenant tenant cannot undo recovery' => [[
        'paid_by' => 'tenant',
        'responsible_party' => 'tenant',
        'settlement_type' => 'none',
        'settled_at' => '2026-07-04 10:00:00',
    ], 'expenses.undo-recovered', 'Această acțiune nu este permisă pentru această cheltuială.'],
    'deduct from rent cannot undo reimbursement' => [[
        'paid_by' => 'tenant',
        'responsible_party' => 'owner',
        'settlement_type' => 'deduct_from_rent',
        'settled_at' => '2026-07-04 10:00:00',
    ], 'expenses.undo-reimbursed', 'Această acțiune nu este permisă pentru această cheltuială.'],
    'deduct from rent cannot undo recovery' => [[
        'paid_by' => 'tenant',
        'responsible_party' => 'owner',
        'settlement_type' => 'deduct_from_rent',
        'settled_at' => '2026-07-04 10:00:00',
    ], 'expenses.undo-recovered', 'Această acțiune nu este permisă pentru această cheltuială.'],
]);

test('vacant property rejects tenant paid expense', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $property = Property::factory()->for($team)->create();

    $response = $this
        ->actingAs($user)
        ->post(route('expenses.store', $team), validExpensePayload($property, [
            'paid_by' => 'tenant',
            'responsible_party' => 'tenant',
            'settlement_type' => 'none',
        ]));

    $response->assertSessionHasErrors(['paid_by', 'responsible_party']);
});

test('vacant property rejects tenant responsible expense', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $property = Property::factory()->for($team)->create();

    $response = $this
        ->actingAs($user)
        ->post(route('expenses.store', $team), validExpensePayload($property, [
            'paid_by' => 'owner',
            'responsible_party' => 'tenant',
            'settlement_type' => 'reimburse',
        ]));

    $response->assertSessionHasErrors(['responsible_party']);
});

test('vacant property rejects tenant paid owner responsible expense', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $property = Property::factory()->for($team)->create();

    $response = $this
        ->actingAs($user)
        ->post(route('expenses.store', $team), validExpensePayload($property, [
            'paid_by' => 'tenant',
            'responsible_party' => 'owner',
            'settlement_type' => 'deduct_from_rent',
        ]));

    $response->assertSessionHasErrors(['paid_by']);
});

test('tenant paid expense is allowed only inside an active contract interval', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $property = Property::factory()->for($team)->create();
    $lease = Lease::factory()->for($team)->create([
        'property_id' => $property->id,
        'start_date' => '2026-06-01',
        'end_date' => '2026-06-30',
    ]);

    $this
        ->actingAs($user)
        ->post(route('expenses.store', $team), validExpensePayload($property, [
            'lease_id' => $lease->id,
            'expense_date' => '2026-06-12',
            'paid_by' => 'tenant',
            'responsible_party' => 'tenant',
            'settlement_type' => 'none',
        ]))
        ->assertRedirect(route('expenses.index', $team));

    $this
        ->actingAs($user)
        ->post(route('expenses.store', $team), validExpensePayload($property, [
            'lease_id' => $lease->id,
            'expense_date' => '2026-07-12',
            'paid_by' => 'tenant',
            'responsible_party' => 'tenant',
            'settlement_type' => 'none',
        ]))
        ->assertSessionHasErrors(['lease_id', 'paid_by', 'responsible_party']);
});

test('vacant property accepts owner paid owner expense', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $property = Property::factory()->for($team)->create();

    $this
        ->actingAs($user)
        ->post(route('expenses.store', $team), validExpensePayload($property, [
            'lease_id' => null,
            'paid_by' => 'owner',
            'responsible_party' => 'owner',
            'settlement_type' => 'none',
        ]))
        ->assertRedirect(route('expenses.index', $team));

    $this->assertDatabaseHas('expenses', [
        'property_id' => $property->id,
        'lease_id' => null,
        'paid_by' => 'owner',
        'responsible_party' => 'owner',
        'settlement_type' => 'none',
        'status' => 'paid',
    ]);
});

test('active contract allows owner paid tenant responsible reimbursement', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $property = Property::factory()->for($team)->create();
    $lease = Lease::factory()->for($team)->create([
        'property_id' => $property->id,
        'start_date' => '2026-06-01',
        'end_date' => '2026-06-30',
    ]);

    $this
        ->actingAs($user)
        ->post(route('expenses.store', $team), validExpensePayload($property, [
            'lease_id' => $lease->id,
            'expense_date' => '2026-06-12',
            'paid_by' => 'owner',
            'responsible_party' => 'tenant',
            'settlement_type' => 'reimburse',
        ]))
        ->assertRedirect(route('expenses.index', $team));

    $this->assertDatabaseHas('expenses', [
        'property_id' => $property->id,
        'lease_id' => $lease->id,
        'paid_by' => 'owner',
        'responsible_party' => 'tenant',
        'settlement_type' => 'reimburse',
        'status' => 'reimbursable',
    ]);
});

test('property with contract outside expense date rejects tenant responsibility', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $property = Property::factory()->for($team)->create();
    $lease = Lease::factory()->for($team)->create([
        'property_id' => $property->id,
        'start_date' => '2026-05-01',
        'end_date' => '2026-05-31',
    ]);

    $this
        ->actingAs($user)
        ->post(route('expenses.store', $team), validExpensePayload($property, [
            'lease_id' => $lease->id,
            'expense_date' => '2026-06-12',
            'paid_by' => 'owner',
            'responsible_party' => 'tenant',
            'settlement_type' => 'reimburse',
        ]))
        ->assertSessionHasErrors(['lease_id', 'responsible_party']);
});

test('users cannot access expenses owned by another workspace', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $otherTeam = Team::factory()->create();
    $expense = Expense::factory()->for($otherTeam)->create();

    $this->withoutExceptionHandling();

    expect(fn () => $this
        ->actingAs($user)
        ->get(route('expenses.show', [$team, $expense])))
        ->toThrow(AuthorizationException::class);
});
