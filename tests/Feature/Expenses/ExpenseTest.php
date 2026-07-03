<?php

use App\Models\Expense;
use App\Models\Lease;
use App\Models\Property;
use App\Models\Team;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            'status' => 'draft',
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
        'status',
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
        'status' => 'reimbursable',
    ]);

    $this
        ->actingAs($user)
        ->delete(route('expenses.destroy', [$team, $expense]))
        ->assertRedirect(route('expenses.index', $team));

    $this->assertDatabaseMissing('expenses', [
        'id' => $expense->id,
    ]);
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
