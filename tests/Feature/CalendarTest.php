<?php

use App\Models\Lease;
use App\Models\RentPayment;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

afterEach(function () {
    Carbon::setTestNow();
});

test('calendar shows rent due and lease boundary events for the current workspace', function () {
    Carbon::setTestNow('2026-10-10 12:00:00');

    $user = User::factory()->create();
    $team = $user->currentTeam;
    $otherTeam = Team::factory()->create();

    $lease = Lease::factory()->for($team)->create([
        'start_date' => '2026-10-01',
        'end_date' => '2026-10-31',
        'monthly_rent_amount' => 2500,
        'currency' => 'RON',
        'rent_due_day' => 5,
    ]);

    Lease::factory()->for($otherTeam)->create([
        'start_date' => '2026-10-01',
        'end_date' => '2026-10-31',
        'monthly_rent_amount' => 9000,
        'rent_due_day' => 5,
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
            ->where('selectedMonth', '2026-10')
            ->where('previousMonth', '2026-09')
            ->where('nextMonth', '2026-11')
            ->where('todayMonth', '2026-10')
            ->where('today', '2026-10-10')
            ->has('events', 3)
            ->where('events.0.kind', 'lease_start')
            ->where('events.0.date', '2026-10-01')
            ->where('events.0.lease_id', $lease->id)
            ->where('events.1.kind', 'rent_due')
            ->where('events.1.date', '2026-10-05')
            ->where('events.1.lease_id', $lease->id)
            ->where('events.1.status_key', 'overdue')
            ->where('events.1.amount', '2500.00')
            ->where('events.1.remaining_amount', '2500.00')
            ->where('events.2.kind', 'lease_end')
            ->where('events.2.date', '2026-10-31')
            ->where('events.2.lease_id', $lease->id)
            ->where('summary.event_count', 3)
            ->where('summary.rent_due_count', 1)
            ->where('summary.overdue_count', 1)
            ->where('summary.lease_change_count', 2)
        );
});

test('calendar rent due event reflects existing payment allocation', function () {
    Carbon::setTestNow('2026-10-10 12:00:00');

    $user = User::factory()->create();
    $team = $user->currentTeam;
    $lease = Lease::factory()->for($team)->create([
        'start_date' => '2026-07-01',
        'end_date' => '2026-12-31',
        'monthly_rent_amount' => 2500,
        'currency' => 'RON',
        'rent_due_day' => 5,
    ]);

    RentPayment::factory()->for($team)->create([
        'lease_id' => $lease->id,
        'property_id' => $lease->property_id,
        'renter_id' => $lease->renter_id,
        'amount' => 2500,
        'currency' => 'RON',
        'payment_type' => 'rent',
        'payment_date' => '2026-10-03',
        'period_month' => 10,
        'period_year' => 2026,
        'status' => 'paid',
    ]);

    $this
        ->actingAs($user)
        ->get(route('calendar.index', [
            'current_team' => $team,
            'month' => '2026-10',
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('events', 1)
            ->where('events.0.kind', 'rent_due')
            ->where('events.0.date', '2026-10-05')
            ->where('events.0.status_key', 'paid')
            ->where('events.0.amount', '2500.00')
            ->where('events.0.remaining_amount', '0.00')
            ->where('summary.overdue_count', 0)
        );
});

test('calendar validates the requested month', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $this
        ->actingAs($user)
        ->from(route('calendar.index', $team))
        ->get(route('calendar.index', [
            'current_team' => $team,
            'month' => '2026-13',
        ]))
        ->assertRedirect(route('calendar.index', $team))
        ->assertSessionHasErrors('month');
});
