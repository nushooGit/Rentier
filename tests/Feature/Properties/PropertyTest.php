<?php

use App\Enums\TeamRole;
use App\Models\Property;
use App\Models\Team;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

function validPropertyPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Central Apartment',
        'type' => 'apartment',
        'country' => 'Romania',
        'city' => 'Bucharest',
        'county_or_sector' => 'Sector 1',
        'address_line' => 'Strada Exemplu 10',
        'postal_code' => '010101',
        'rooms' => 2,
        'usable_area_sqm' => 58.5,
        'floor' => 3,
        'total_floors' => 8,
        'status' => 'available',
        'monthly_rent_amount' => 2500,
        'currency' => 'RON',
        'rent_due_day' => 5,
        'deposit_amount' => 2500,
        'notes' => 'Near metro.',
    ], $overrides);
}

test('properties index shows properties for the current workspace', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $otherTeam = Team::factory()->create();

    Property::factory()->for($team)->create(['name' => 'Visible Property']);
    Property::factory()->for($otherTeam)->create(['name' => 'Hidden Property']);

    $response = $this
        ->actingAs($user)
        ->get(route('properties.index', $team));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('properties/index')
            ->has('properties', 1)
            ->where('properties.0.name', 'Visible Property')
        );
});

test('workspace members can create properties', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $response = $this
        ->actingAs($user)
        ->post(route('properties.store', $team), validPropertyPayload());

    $property = Property::first();

    $response->assertRedirect(route('properties.show', [$team, $property]));

    $this->assertDatabaseHas('properties', [
        'team_id' => $team->id,
        'name' => 'Central Apartment',
        'city' => 'Bucharest',
        'address_line' => 'Strada Exemplu 10',
    ]);
});

test('property validation requires core fields', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $response = $this
        ->actingAs($user)
        ->post(route('properties.store', $team), validPropertyPayload([
            'name' => '',
            'city' => '',
            'address_line' => '',
            'rent_due_day' => 32,
            'monthly_rent_amount' => -1,
        ]));

    $response->assertSessionHasErrors([
        'name',
        'city',
        'address_line',
        'rent_due_day',
        'monthly_rent_amount',
    ]);
});

test('workspace members can view and update properties regardless of team role', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $team = Team::factory()->create();

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
    $team->members()->attach($member, ['role' => TeamRole::Member->value]);

    $property = Property::factory()->for($team)->create([
        'name' => 'Original Property',
    ]);

    $this
        ->actingAs($member)
        ->get(route('properties.show', [$team, $property]))
        ->assertOk();

    $response = $this
        ->actingAs($member)
        ->patch(route('properties.update', [$team, $property]), validPropertyPayload([
            'name' => 'Updated Property',
            'status' => 'occupied',
        ]));

    $response->assertRedirect(route('properties.show', [$team, $property]));

    $this->assertDatabaseHas('properties', [
        'id' => $property->id,
        'name' => 'Updated Property',
        'status' => 'occupied',
    ]);
});

test('users cannot access a workspace they do not belong to', function () {
    $user = User::factory()->create();
    $otherTeam = Team::factory()->create();

    $this->withoutExceptionHandling();

    expect(fn () => $this
        ->actingAs($user)
        ->get(route('properties.index', $otherTeam)))
        ->toThrow(HttpException::class);
});

test('users cannot access properties owned by another workspace', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $otherTeam = Team::factory()->create();
    $property = Property::factory()->for($otherTeam)->create();

    $this->withoutExceptionHandling();

    expect(fn () => $this
        ->actingAs($user)
        ->get(route('properties.show', [$team, $property])))
        ->toThrow(AuthorizationException::class);
});

test('workspace members can delete properties', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $property = Property::factory()->for($team)->create();

    $response = $this
        ->actingAs($user)
        ->delete(route('properties.destroy', [$team, $property]));

    $response->assertRedirect(route('properties.index', $team));

    $this->assertDatabaseMissing('properties', [
        'id' => $property->id,
    ]);
});
