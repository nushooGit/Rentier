<?php

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    config(['app.url' => 'https://app.rentier.ro']);
});

test('apex login redirects to the canonical app host and preserves query context', function () {
    $this->get('https://rentier.ro/login?invitation=sample-code')
        ->assertRedirect('https://app.rentier.ro/login?invitation=sample-code');
});

test('canonical app login remains directly available', function () {
    $this->get('https://app.rentier.ro/login')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('auth/login'));
});

test('authenticated apex workspace routes redirect to the canonical app host', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $this->actingAs($user)
        ->get("https://rentier.ro/{$team->slug}/dashboard")
        ->assertRedirect("https://app.rentier.ro/{$team->slug}/dashboard");
});

test('unsafe apex app requests are not executed and return to the canonical app root', function () {
    $user = User::factory()->create([
        'password' => bcrypt('Secret123!'),
    ]);

    $this->post('https://rentier.ro/login', [
        'email' => $user->email,
        'password' => 'Secret123!',
    ])->assertStatus(303)
        ->assertRedirect('https://app.rentier.ro/');

    $this->assertGuest();
});

test('legacy apex signed verification links are still validated on their original host', function () {
    $user = User::factory()->unverified()->create();

    Event::fake();
    URL::forceRootUrl('https://rentier.ro');
    URL::forceScheme('https');

    try {
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)],
        );

        $this->actingAs($user)->get($verificationUrl);

        Event::assertDispatched(Verified::class);
        expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    } finally {
        URL::forceRootUrl(null);
        URL::forceScheme(null);
    }
});

test('apex root remains the public landing page', function () {
    $this->get('https://rentier.ro/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('welcome'));
});
