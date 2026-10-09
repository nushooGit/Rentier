<?php

use App\Actions\Renters\ManageRenterInvitation;
use App\Models\Renter;
use App\Models\RenterInvitation;
use App\Models\Team;
use App\Models\User;
use Illuminate\Validation\ValidationException;

function privateRenterFixture(): array
{
    $owner = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $owner->id]);
    $renter = Renter::factory()->create([
        'team_id' => $team->id,
        'user_id' => null,
        'email' => 'renter@example.com',
    ]);

    return [$owner, $team, $renter];
}

test('private invitation only stores a hash and accepts the verified recipient once', function () {
    [$owner, $team, $renter] = privateRenterFixture();
    $recipient = User::factory()->create(['email' => 'renter@example.com', 'email_verified_at' => now()]);
    $action = app(ManageRenterInvitation::class);
    $issued = $action->issue($owner, $renter);

    expect($issued['token'])->toHaveLength(64)
        ->and($issued['invitation']->token_hash)->toBe(hash('sha256', $issued['token']))
        ->and($issued['invitation']->token_hash)->not->toBe($issued['token']);

    $linked = $action->accept($recipient, $issued['token']);
    expect($linked->user_id)->toBe($recipient->id)
        ->and($recipient->belongsToTeam($team))->toBeFalse();

    expect(fn () => $action->accept($recipient, $issued['token']))->toThrow(ValidationException::class);
});

test('other workspace owners cannot issue or revoke renter invitations', function () {
    [$owner, , $renter] = privateRenterFixture();
    $stranger = User::factory()->create();
    $action = app(ManageRenterInvitation::class);

    expect(fn () => $action->issue($stranger, $renter))->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
    $issued = $action->issue($owner, $renter);

    expect(fn () => $action->revoke($stranger, $issued['invitation']))->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
});

test('matching an unverified account or a different email never grants a renter link', function () {
    [$owner, , $renter] = privateRenterFixture();
    $action = app(ManageRenterInvitation::class);
    $issued = $action->issue($owner, $renter);

    $unverified = User::factory()->create(['email' => 'renter@example.com', 'email_verified_at' => null]);
    $wrongEmail = User::factory()->create(['email' => 'other@example.com', 'email_verified_at' => now()]);

    expect(fn () => $action->accept($unverified, $issued['token']))->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class)
        ->and(fn () => $action->accept($wrongEmail, $issued['token']))->toThrow(ValidationException::class)
        ->and($renter->fresh()->user_id)->toBeNull();
});

test('expired and revoked invitations cannot be used', function () {
    [$owner, , $renter] = privateRenterFixture();
    $recipient = User::factory()->create(['email' => 'renter@example.com', 'email_verified_at' => now()]);
    $action = app(ManageRenterInvitation::class);

    $expired = $action->issue($owner, $renter);
    $expired['invitation']->update(['expires_at' => now()->subMinute()]);
    expect(fn () => $action->accept($recipient, $expired['token']))->toThrow(ValidationException::class);

    $revoked = $action->issue($owner, $renter);
    $action->revoke($owner, $revoked['invitation']);
    expect(fn () => $action->accept($recipient, $revoked['token']))->toThrow(ValidationException::class);
});

test('a claimed renter record cannot be silently reassigned', function () {
    [$owner, , $renter] = privateRenterFixture();
    $action = app(ManageRenterInvitation::class);
    $issued = $action->issue($owner, $renter);
    $existing = User::factory()->create();
    $renter->update(['user_id' => $existing->id]);
    $recipient = User::factory()->create(['email' => 'renter@example.com', 'email_verified_at' => now()]);

    expect(fn () => $action->accept($recipient, $issued['token']))->toThrow(ValidationException::class)
        ->and($renter->fresh()->user_id)->toBe($existing->id);
});
