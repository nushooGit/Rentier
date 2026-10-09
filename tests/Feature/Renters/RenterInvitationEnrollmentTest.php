<?php

use App\Actions\Renters\ManageRenterInvitation;
use App\Enums\TeamRole;
use App\Models\Renter;
use App\Models\Team;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;
use Inertia\Testing\AssertableInertia as Assert;

function makeEnrollmentInvitation(): array
{
    $owner = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
    $renter = Renter::factory()->create(['team_id' => $team->id, 'user_id' => null, 'email' => 'guest@example.com']);
    $token = app(ManageRenterInvitation::class)->issue($owner, $renter)['token'];

    return [$renter, $token];
}

test('invitation landing is available without public registration', function () {
    config(['fortify.features' => []]);
    [, $token] = makeEnrollmentInvitation();

    $this->get(route('renter-invitations.show', ['token' => $token]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('renter-invitations/show')
            ->where('email', 'guest@example.com')
            ->where('authenticated', false)
            ->etc());
});

test('invitation-only enrollment creates user without immediately linking renter', function () {
    Event::fake([Registered::class]);
    [$renter, $token] = makeEnrollmentInvitation();

    $this->withoutExceptionHandling();
    $this->post(route('renter-invitations.register', ['token' => $token]), [
        'name' => 'Invited Renter',
        'email' => 'guest@example.com',
        'password' => 'ComplexPass123!',
        'password_confirmation' => 'ComplexPass123!',
    ])->assertRedirect(route('renter-invitations.show', ['token' => $token]));

    $user = User::query()->where('email', 'guest@example.com')->firstOrFail();
    $this->assertAuthenticatedAs($user);
    expect($renter->fresh()->user_id)->toBeNull();
    Event::assertDispatched(Registered::class);
});

test('wrong invitation email is denied before creating account', function () {
    [, $token] = makeEnrollmentInvitation();

    $this->post(route('renter-invitations.register', ['token' => $token]), [
        'name' => 'Wrong',
        'email' => 'wrong@example.com',
        'password' => 'ComplexPass123!',
        'password_confirmation' => 'ComplexPass123!',
    ])->assertSessionHasErrors('email');

    $this->assertDatabaseMissing('users', ['email' => 'wrong@example.com']);
});

test('revoked invitation cannot register a new account', function () {
    [$renter, $token] = makeEnrollmentInvitation();
    $invitation = \App\Models\RenterInvitation::query()->where('renter_id', $renter->id)->firstOrFail();
    $invitation->update(['revoked_at' => now()]);

    $this->post(route('renter-invitations.register', ['token' => $token]), [
        'name' => 'Invited',
        'email' => 'guest@example.com',
        'password' => 'ComplexPass123!',
        'password_confirmation' => 'ComplexPass123!',
    ])->assertSessionHasErrors('invitation');

    $this->assertDatabaseMissing('users', ['email' => 'guest@example.com']);
});
