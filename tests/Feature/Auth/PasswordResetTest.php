<?php

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::resetPasswords());
});

test('reset password link screen can be rendered', function () {
    $response = $this->get(route('password.request'));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('auth/forgot-password'));
});

test('reset password link can be requested', function () {
    Notification::fake();

    $user = User::factory()->create();

    $response = $this->post(route('password.email'), ['email' => $user->email]);

    $response->assertSessionHas('status', __('passwords.sent'));
    Notification::assertSentTo($user, ResetPasswordNotification::class);
});

test('unknown email receives the same safe response without sending mail', function () {
    Notification::fake();

    $response = $this->post(route('password.email'), ['email' => 'necunoscut@example.com']);

    $response
        ->assertSessionHasNoErrors()
        ->assertSessionHas('status', __('passwords.sent'));
    Notification::assertNothingSent();
});

test('repeated reset requests cannot disclose whether the email belongs to an account', function () {
    Notification::fake();

    $user = User::factory()->create();

    $first = $this->post(route('password.email'), ['email' => $user->email]);
    $throttled = $this->post(route('password.email'), ['email' => $user->email]);
    $unknown = $this->post(route('password.email'), ['email' => 'necunoscut@example.com']);

    foreach ([$first, $throttled, $unknown] as $response) {
        $response
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', __('passwords.sent'));
    }

    Notification::assertSentTo($user, ResetPasswordNotification::class, 1);
    Notification::assertCount(1);
});

test('JSON reset requests return the same response for unknown and throttled email', function () {
    Notification::fake();

    $user = User::factory()->create();

    $first = $this->postJson(route('password.email'), ['email' => $user->email]);
    $throttled = $this->postJson(route('password.email'), ['email' => $user->email]);
    $unknown = $this->postJson(route('password.email'), ['email' => 'necunoscut@example.com']);

    foreach ([$first, $throttled, $unknown] as $response) {
        $response
            ->assertOk()
            ->assertJson(['message' => __('passwords.sent')]);
    }

    Notification::assertSentTo($user, ResetPasswordNotification::class, 1);
    Notification::assertCount(1);
});

test('password reset request validates email in Romanian', function () {
    $response = $this->post(route('password.email'), ['email' => 'adresă-invalidă']);

    $response->assertSessionHasErrors([
        'email' => 'Adresa de email trebuie să fie validă.',
    ]);
});

test('reset password screen can be rendered', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post(route('password.email'), ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPasswordNotification::class, function ($notification) use ($user) {
        $response = $this->get(route('password.reset', [
            'token' => $notification->token,
            'email' => $user->email,
        ]));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('auth/reset-password')
                ->where('email', $user->email)
                ->where('token', $notification->token));

        return true;
    });
});

test('password can be reset with valid token', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post(route('password.email'), ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPasswordNotification::class, function ($notification) use ($user) {
        $response = $this->post(route('password.update'), [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('login'));

        expect(Hash::check('password', $user->fresh()->password))->toBeTrue();

        return true;
    });
});

test('password cannot be reset with invalid token', function () {
    $user = User::factory()->create();

    $response = $this->post(route('password.update'), [
        'token' => 'invalid-token',
        'email' => $user->email,
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ]);

    $response->assertSessionHasErrors('email');
    expect(Hash::check('newpassword123', $user->fresh()->password))->toBeFalse();
});

test('password cannot be reset with an expired token', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post(route('password.email'), ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPasswordNotification::class, function ($notification) use ($user) {
        $this->travel((int) config('auth.passwords.users.expire') + 1)->minutes();

        $response = $this->post(route('password.update'), [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertSessionHasErrors([
            'email' => __('passwords.token'),
        ]);
        expect(Hash::check('newpassword123', $user->fresh()->password))->toBeFalse();

        return true;
    });
});

test('password reset email is Romanian and uses the configured application URL', function () {
    config(['app.url' => 'https://configured-host.example']);

    $user = User::factory()->create();
    $notification = new ResetPasswordNotification('test-token');
    $mail = $notification->toMail($user);

    expect($mail->subject)->toBe('Resetează parola contului Rentier')
        ->and($mail->actionText)->toBe('Resetează parola')
        ->and($mail->actionUrl)->toStartWith('https://configured-host.example/reset-password/test-token')
        ->and($mail->actionUrl)->toContain(urlencode($user->email))
        ->and(implode(' ', $mail->introLines))->toContain('solicitată resetarea parolei')
        ->and(implode(' ', $mail->outroLines))->toContain('poți ignora acest mesaj');
});
