<?php

use App\Models\Team;
use App\Models\TeamInvitation as TeamInvitationModel;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\Teams\TeamInvitation as TeamInvitationNotification;
use Illuminate\Support\Facades\URL;

test('app subdomain retains the apex passkey relying party when configured', function () {
    $originalUrl = config('app.url');
    $previousServer = $_SERVER['PASSKEYS_RELYING_PARTY_ID'] ?? null;
    $previousEnv = $_ENV['PASSKEYS_RELYING_PARTY_ID'] ?? null;

    try {
        config(['app.url' => 'https://app.rentier.ro']);
        $_SERVER['PASSKEYS_RELYING_PARTY_ID'] = 'rentier.ro';
        $_ENV['PASSKEYS_RELYING_PARTY_ID'] = 'rentier.ro';

        $fortify = require base_path('config/fortify.php');

        expect($fortify['passkeys']['relying_party_id'])->toBe('rentier.ro')
            ->and($fortify['passkeys']['allowed_origins'])->toBe(['https://app.rentier.ro']);
    } finally {
        config(['app.url' => $originalUrl]);

        if ($previousServer === null) {
            unset($_SERVER['PASSKEYS_RELYING_PARTY_ID']);
        } else {
            $_SERVER['PASSKEYS_RELYING_PARTY_ID'] = $previousServer;
        }

        if ($previousEnv === null) {
            unset($_ENV['PASSKEYS_RELYING_PARTY_ID']);
        } else {
            $_ENV['PASSKEYS_RELYING_PARTY_ID'] = $previousEnv;
        }
    }
});

test('app subdomain reset invitation and signed verification links use the new host', function () {
    $originalUrl = config('app.url');
    config(['app.url' => 'https://app.rentier.ro']);
    URL::forceRootUrl('https://app.rentier.ro');
    URL::forceScheme('https');

    try {
        $user = User::factory()->create();
        $team = Team::factory()->create();

        $invitation = TeamInvitationModel::factory()->create([
            'team_id' => $team->id,
            'invited_by' => $user->id,
            'email' => 'invitee@example.com',
        ]);

        $reset = (new ResetPasswordNotification('sample-token'))->toMail($user);
        $invite = (new TeamInvitationNotification($invitation))->toMail((object) []);
        $verification = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]);

        expect($reset->actionUrl)->toStartWith('https://app.rentier.ro/reset-password/sample-token')
            ->and($invite->actionUrl)->toStartWith('https://app.rentier.ro/login?invitation=')
            ->and($verification)->toStartWith('https://app.rentier.ro/')
            ->and($verification)->toContain('signature=');
    } finally {
        URL::forceRootUrl(null);
        URL::forceScheme(null);
        config(['app.url' => $originalUrl]);
    }
});
