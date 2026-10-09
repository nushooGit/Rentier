<?php

use App\Models\Lease;
use App\Models\Renter;
use App\Models\RenterInvitation;
use App\Models\Team;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('renter portal only shows leases linked to signed in verified user', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $other = User::factory()->create(['email_verified_at' => now()]);
    $team = Team::factory()->create();

    $mine = Renter::factory()->create(['team_id' => $team->id, 'user_id' => $user->id]);
    $notMine = Renter::factory()->create(['team_id' => $team->id, 'user_id' => $other->id]);

    RenterInvitation::create([
        'team_id' => $team->id,
        'renter_id' => $mine->id,
        'invited_by' => $other->id,
        'email' => $user->email,
        'token_hash' => hash('sha256', 'test-invitation-provenance'),
        'expires_at' => now()->addDay(),
        'accepted_at' => now(),
    ]);

    $mineLease = Lease::factory()->create(['team_id' => $team->id, 'renter_id' => $mine->id]);
    $otherLease = Lease::factory()->create(['team_id' => $team->id, 'renter_id' => $notMine->id]);

    $this->actingAs($user)
        ->get(route('renter.overview'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('renters/overview')
            ->has('leases', 1)
            ->where('leases.0.id', $mineLease->id)
            ->etc());

    expect($mineLease->id)->not->toBe($otherLease->id);
});

test('email matching without an explicit renter user link grants no portal data', function () {
    $user = User::factory()->create(['email' => 'matching@example.com', 'email_verified_at' => now()]);
    $team = Team::factory()->create();
    $renter = Renter::factory()->create(['team_id' => $team->id, 'email' => 'matching@example.com', 'user_id' => null]);
    Lease::factory()->create(['team_id' => $team->id, 'renter_id' => $renter->id]);

    $this->actingAs($user)
        ->get(route('renter.overview'))
        ->assertInertia(fn (Assert $page) => $page->component('renters/overview')->has('leases', 0));
});

test('unauthenticated visitors cannot read renter portal', function () {
    $this->get(route('renter.overview'))->assertRedirect(route('login'));
});

test('legacy user link without accepted invitation is not trusted for portal access', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $team = Team::factory()->create();
    $renter = Renter::factory()->create(['team_id' => $team->id, 'user_id' => $user->id]);
    Lease::factory()->create(['team_id' => $team->id, 'renter_id' => $renter->id]);

    $this->actingAs($user)
        ->get(route('renter.overview'))
        ->assertInertia(fn (Assert $page) => $page->component('renters/overview')->has('leases', 0));
});
