<?php

use App\Models\Lease;
use App\Models\Property;
use App\Models\Reminder;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

afterEach(function () {
    Carbon::setTestNow();
});

test('workspace member can create a reminder linked to a property', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $property = Property::factory()->for($team)->create();

    $this
        ->actingAs($user)
        ->post(route('reminders.store', $team), [
            'title' => 'Verifică asigurarea',
            'remind_on' => '2026-10-18',
            'property_id' => $property->id,
            'lease_id' => null,
            'notes' => 'Verifică data expirării.',
        ])
        ->assertRedirect();

    $reminder = Reminder::query()->firstOrFail();

    expect($reminder)
        ->team_id->toBe($team->id)
        ->property_id->toBe($property->id)
        ->created_by_user_id->toBe($user->id)
        ->title->toBe('Verifică asigurarea')
        ->notes->toBe('Verifică data expirării.');

    expect($reminder->remind_on->toDateString())->toBe('2026-10-18');
});

test('lease-linked reminder always uses the lease property', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $lease = Lease::factory()->for($team)->create();
    $otherProperty = Property::factory()->for($team)->create();

    $this
        ->actingAs($user)
        ->post(route('reminders.store', $team), [
            'title' => 'Sună chiriașul',
            'remind_on' => '2026-10-20',
            'property_id' => $otherProperty->id,
            'lease_id' => $lease->id,
            'notes' => null,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('reminders', [
        'team_id' => $team->id,
        'lease_id' => $lease->id,
        'property_id' => $lease->property_id,
    ]);
});

test('workspace member can update toggle and delete a reminder', function () {
    Carbon::setTestNow('2026-10-10 12:00:00');

    $user = User::factory()->create();
    $team = $user->currentTeam;
    $reminder = Reminder::query()->create([
        'team_id' => $team->id,
        'created_by_user_id' => $user->id,
        'title' => 'Reminder inițial',
        'remind_on' => '2026-10-12',
    ]);

    $this
        ->actingAs($user)
        ->put(route('reminders.update', [$team, $reminder]), [
            'title' => 'Reminder actualizat',
            'remind_on' => '2026-10-15',
            'property_id' => null,
            'lease_id' => null,
            'notes' => 'Detalii noi',
        ])
        ->assertRedirect();

    expect($reminder->fresh())
        ->title->toBe('Reminder actualizat')
        ->notes->toBe('Detalii noi')
        ->completed_at->toBeNull();

    $this
        ->actingAs($user)
        ->patch(route('reminders.toggle-complete', [$team, $reminder]))
        ->assertRedirect();

    expect($reminder->fresh()->completed_at)->not->toBeNull();

    $this
        ->actingAs($user)
        ->patch(route('reminders.toggle-complete', [$team, $reminder]))
        ->assertRedirect();

    expect($reminder->fresh()->completed_at)->toBeNull();

    $this
        ->actingAs($user)
        ->delete(route('reminders.destroy', [$team, $reminder]))
        ->assertRedirect();

    $this->assertDatabaseMissing('reminders', ['id' => $reminder->id]);
});

test('reminder actions cannot cross workspace boundaries', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $otherTeam = Team::factory()->create();
    $reminder = Reminder::query()->create([
        'team_id' => $otherTeam->id,
        'title' => 'Alt workspace',
        'remind_on' => '2026-10-12',
    ]);

    $this
        ->actingAs($user)
        ->delete(route('reminders.destroy', [$team, $reminder]))
        ->assertForbidden();

    $this->assertDatabaseHas('reminders', ['id' => $reminder->id]);
});

test('calendar includes reminder state and excludes reminders from other workspaces', function () {
    Carbon::setTestNow('2026-10-10 12:00:00');

    $user = User::factory()->create();
    $team = $user->currentTeam;
    $otherTeam = Team::factory()->create();
    $property = Property::factory()->for($team)->create();

    $reminder = Reminder::query()->create([
        'team_id' => $team->id,
        'property_id' => $property->id,
        'created_by_user_id' => $user->id,
        'title' => 'Revizie centrală',
        'remind_on' => '2026-10-22',
        'notes' => 'Programare service.',
        'completed_at' => now(),
    ]);

    Reminder::query()->create([
        'team_id' => $otherTeam->id,
        'title' => 'Nu trebuie afișat',
        'remind_on' => '2026-10-22',
    ]);

    $this
        ->actingAs($user)
        ->get(route('calendar.index', [
            'current_team' => $team,
            'month' => '2026-10',
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('calendar/index')
            ->where('events.0.kind', 'reminder')
            ->where('events.0.reminder_id', $reminder->id)
            ->where('events.0.title', 'Revizie centrală')
            ->where('events.0.completed', true)
            ->where('events.0.property_id', $property->id)
            ->where('summary.reminder_count', 1)
            ->where('summary.open_reminder_count', 0)
            ->has('properties', 1)
        );
});
