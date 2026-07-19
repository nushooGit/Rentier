<?php

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Fortify\Features;
use Symfony\Component\Process\Process;

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('registration screen includes team invitation context', function () {
    $owner = User::factory()->create();
    $team = Team::factory()->create(['name' => 'Laravel Team']);
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $invitation = TeamInvitation::factory()->create([
        'team_id' => $team->id,
        'email' => 'invited@example.com',
        'invited_by' => $owner->id,
    ]);

    $response = $this->get(route('register', ['invitation' => $invitation->code]));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('auth/register')
        ->where('teamInvitation.code', $invitation->code)
        ->where('teamInvitation.teamName', 'Laravel Team'),
    );
});

test('new users can register', function () {
    Notification::fake();

    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();

    $user = User::where('email', 'test@example.com')->first();
    $response->assertRedirect(route('dashboard'));
    expect($user->hasVerifiedEmail())->toBeFalse();
    Notification::assertSentTo($user, VerifyEmail::class);
});

test('registration feature is enabled by default', function () {
    expect(Features::enabled(Features::registration()))->toBeTrue();

    $this->get('/register')->assertOk();
});

test('registration routes are unavailable when private beta registration is disabled', function () {
    $registerRoutes = new Process([
        PHP_BINARY,
        'artisan',
        'route:list',
        '--name=register',
        '--no-ansi',
    ], base_path(), ['RENTIER_REGISTRATION_ENABLED' => 'false']);

    $registerRoutes->mustRun();

    expect($registerRoutes->getOutput())
        ->not->toContain('register.store')
        ->not->toContain(' register ');

    $loginRoutes = new Process([
        PHP_BINARY,
        'artisan',
        'route:list',
        '--name=login',
        '--no-ansi',
    ], base_path(), ['RENTIER_REGISTRATION_ENABLED' => 'false']);

    $loginRoutes->mustRun();

    expect($loginRoutes->getOutput())
        ->toContain('login.store')
        ->toContain(' login ');
});
