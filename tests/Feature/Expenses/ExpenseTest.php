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
            ->where('expenses.0.settlement_state.action_label', null)
        );

    $this
        ->actingAs($user)
        ->patch(route('expenses.mark-reimbursed', [$team, $expense]))
        ->assertSessionHasErrors(['expense']);

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
            ->where('expenses.0.settlement_state.action_label', null)
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

    $response->assertSessionHasErrors(['paid_by']);
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
