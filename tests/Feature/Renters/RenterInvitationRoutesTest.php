<?php

use App\Enums\TeamRole;
use App\Models\Renter;
use App\Models\Team;
use App\Models\User;
use App\Notifications\Renters\RenterPortalInvitation;
use Illuminate\Support\Facades\Notification;

test('workspace owner can issue invite but no outsider can use the endpoint', function () {
    Notification::fake();
    $owner = User::factory()->create();
    $outsider = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
    $renter = Renter::factory()->create(['team_id' => $team->id, 'user_id' => null, 'email' => 'renter@example.com']);

    $this->actingAs($owner)
        ->post(route('renters.invitations.store', ['current_team' => $team->slug, 'renter' => $renter->id]))
        ->assertRedirect();

    Notification::assertSentOnDemand(RenterPortalInvitation::class);

    $this->actingAs($outsider)
        ->post(route('renters.invitations.store', ['current_team' => $team->slug, 'renter' => $renter->id]))
        ->assertForbidden();
});

test('renter cannot accept a claim without verified email', function () {
    $unverified = User::factory()->create(['email_verified_at' => null]);

    $this->actingAs($unverified)
        ->post(route('renter-invitations.accept'), ['token' => str_repeat('a', 64)])
        ->assertRedirect();
});

test('verified invited user accepts and reaches only their renter portal', function () {
    $owner = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
    $renter = Renter::factory()->create(['team_id' => $team->id, 'user_id' => null, 'email' => 'guest@example.com']);
    $token = app(\\App\\Actions\\Renters\\ManageRenterInvitation::class)->issue($owner, $renter)['token'];
    $recipient = User::factory()->create(['email' => 'guest@example.com', 'email_verified_at' => now()]);

    $this->actingAs($recipient)
        ->post(route('renter-invitations.accept'), ['token' => $token])
        ->assertRedirect(route('renter.overview'));

    expect($renter->fresh()->user_id)->toBe($recipient->id)
        ->and($recipient->belongsToTeam($team))->toBeFalse();
});
