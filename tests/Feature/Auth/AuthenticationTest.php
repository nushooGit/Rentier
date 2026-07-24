<?php

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Fortify\Features;
use Laravel\Passkeys\Contracts\PasskeyLoginResponse;

test('login screen can be rendered', function () {
    $response = $this->get(route('login'));

    $response->assertOk();
});

test('login screen includes team invitation context', function () {
    $owner = User::factory()->create();
    $team = Team::factory()->create(['name' => 'Laravel Team']);
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $invitation = TeamInvitation::factory()->create([
        'team_id' => $team->id,
        'email' => 'invited@example.com',
        'invited_by' => $owner->id,
    ]);

    $response = $this->get(route('login', ['invitation' => $invitation->code]));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('auth/login')
        ->where('teamInvitation.code', $invitation->code)
        ->where('teamInvitation.teamName', 'Laravel Team'),
    );
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard'));
});

test('proxied https requests are treated as secure', function () {
    Route::get('/_test/proxy-context', fn (Request $request) => [
        'is_secure' => $request->isSecure(),
        'host' => $request->getHost(),
        'port' => $request->getPort(),
        'ip' => $request->ip(),
        'url' => $request->fullUrl(),
    ]);

    $response = $this
        ->withServerVariables([
            'REMOTE_ADDR' => '172.18.0.10',
            'SERVER_PORT' => '80',
            'HTTPS' => 'off',
        ])
        ->withHeaders([
            'Host' => 'rentier-app:8080',
            'X-Forwarded-For' => '203.0.113.50',
            'X-Forwarded-Host' => 'rentier.ro',
            'X-Forwarded-Port' => '443',
            'X-Forwarded-Proto' => 'https',
        ])
        ->get('/_test/proxy-context?status=ok');

    $response->assertOk();
    $response->assertJson([
        'is_secure' => true,
        'host' => 'rentier.ro',
        'port' => 443,
        'ip' => '203.0.113.50',
        'url' => 'https://rentier.ro/_test/proxy-context?status=ok',
    ]);
});

test('proxied https login redirects keep the forwarded scheme', function () {
    $user = User::factory()->create();

    $response = $this
        ->withServerVariables([
            'REMOTE_ADDR' => '172.18.0.10',
            'SERVER_PORT' => '80',
            'HTTPS' => 'off',
        ])
        ->withHeaders([
            'Host' => 'rentier-app:8080',
            'X-Forwarded-For' => '203.0.113.50',
            'X-Forwarded-Host' => 'rentier.ro',
            'X-Forwarded-Port' => '443',
            'X-Forwarded-Proto' => 'https',
        ])
        ->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

    $this->assertAuthenticated();
    $response->assertRedirect("https://rentier.ro/{$user->personalTeam()->slug}/dashboard");
});

test('local requests without forwarded headers keep normal request context', function () {
    Route::get('/_test/local-context', fn (Request $request) => [
        'is_secure' => $request->isSecure(),
        'host' => $request->getHost(),
        'url' => $request->fullUrl(),
    ]);

    $response = $this->get('/_test/local-context');

    $response->assertOk();
    $response->assertJson([
        'is_secure' => false,
        'host' => 'localhost',
        'url' => 'http://localhost/_test/local-context',
    ]);
});

test('passkey login response redirects to the current team dashboard', function () {
    $user = User::factory()->create();

    $request = Request::create(route('login', absolute: false), 'GET', server: [
        'HTTP_ACCEPT' => 'application/json',
    ]);
    $request->setLaravelSession($this->app['session.store']);
    $request->setUserResolver(fn () => $user);

    $jsonResponse = app(PasskeyLoginResponse::class)->toResponse($request);

    expect($jsonResponse->getData()->redirect)->toBe(route('dashboard', ['current_team' => $user->personalTeam()->slug]));
});

test('users with two factor enabled are redirected to two factor challenge', function () {
    if (! Features::canManageTwoFactorAuthentication()) {
        $this->markTestSkipped('Two-factor authentication is not enabled.');
    }

    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]);

    $user = User::factory()->withTwoFactor()->create();

    $response = $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('two-factor.login'));
    $response->assertSessionHas('login.id', $user->id);
    $this->assertGuest();
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('logout'));

    $this->assertGuest();
    $response->assertRedirect(route('home'));
});

test('users are rate limited', function () {
    $user = User::factory()->create();

    RateLimiter::increment(md5('login'.implode('|', [$user->email, '127.0.0.1'])), amount: 5);

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertTooManyRequests();
});
