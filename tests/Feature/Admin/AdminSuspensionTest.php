<?php

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('platform admin can suspend a user and invalidate database sessions', function () {
    $admin = User::factory()->create(['email' => 'operator@example.com']);
    $target = User::factory()->create([
        'email' => 'landlord@example.com',
        'remember_token' => 'old-remember-token',
    ]);

    config(['rentier.platform_admin_emails' => [$admin->email]]);
    config(['session.driver' => 'database']);

    DB::table('sessions')->insert([
        'id' => 'target-session',
        'user_id' => $target->id,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Pest',
        'payload' => '',
        'last_activity' => now()->timestamp,
    ]);

    $this->actingAs($admin)
        ->patch("http://admin.localhost/users/{$target->id}/suspend", [
            'reason' => 'Verificare necesară.',
        ])
        ->assertRedirect();

    $target->refresh();

    expect($target->isSuspended())->toBeTrue()
        ->and($target->suspension_reason)->toBe('Verificare necesară.')
        ->and($target->suspended_by_user_id)->toBe($admin->id)
        ->and($target->remember_token)->not->toBe('old-remember-token')
        ->and(DB::table('sessions')->where('user_id', $target->id)->exists())->toBeFalse();
});

test('suspended users cannot authenticate with password', function () {
    $user = User::factory()->create([
        'email' => 'suspended@example.com',
    ]);

    $user->forceFill([
        'suspended_at' => now(),
        'suspension_reason' => 'Test',
    ])->save();

    $this->post('http://localhost/login', [
        'email' => $user->email,
        'password' => 'password',
    ])
        ->assertSessionHasErrors([
            'email' => 'Contul tău Rentier este suspendat. Contactează suportul dacă ai nevoie de ajutor.',
        ]);

    $this->assertGuest();
});

test('existing authenticated session is rejected after user suspension', function () {
    $user = User::factory()->create();

    $user->forceFill([
        'suspended_at' => now(),
        'suspension_reason' => 'Test',
    ])->save();

    $this->actingAs($user)
        ->get('http://localhost/settings/profile')
        ->assertRedirect('http://localhost/login')
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('platform admins cannot be suspended from the admin interface', function () {
    $admin = User::factory()->create(['email' => 'operator@example.com']);
    $otherAdmin = User::factory()->create(['email' => 'second-admin@example.com']);

    config(['rentier.platform_admin_emails' => [$admin->email, $otherAdmin->email]]);

    $this->actingAs($admin)
        ->patch("http://admin.localhost/users/{$otherAdmin->id}/suspend", [
            'reason' => 'Should not be allowed.',
        ])
        ->assertStatus(422);

    expect($otherAdmin->fresh()->isSuspended())->toBeFalse();
});

test('platform admin can reactivate a suspended user', function () {
    $admin = User::factory()->create(['email' => 'operator@example.com']);
    $target = User::factory()->create(['email' => 'landlord@example.com']);

    config(['rentier.platform_admin_emails' => [$admin->email]]);

    $target->forceFill([
        'suspended_at' => now(),
        'suspension_reason' => 'Verificare',
        'suspended_by_user_id' => $admin->id,
    ])->save();

    $this->actingAs($admin)
        ->patch("http://admin.localhost/users/{$target->id}/reactivate")
        ->assertRedirect();

    $target->refresh();

    expect($target->isSuspended())->toBeFalse()
        ->and($target->reactivated_at)->not->toBeNull()
        ->and($target->reactivated_by_user_id)->toBe($admin->id)
        ->and($target->suspension_reason)->toBe('Verificare');
});

test('suspended workspace blocks member operations and can be reactivated', function () {
    $admin = User::factory()->create(['email' => 'operator@example.com']);
    $member = User::factory()->create(['email' => 'member@example.com']);
    $workspace = Team::factory()->create([
        'name' => 'Portofoliu suspendabil',
        'slug' => 'portofoliu-suspendabil',
    ]);

    $workspace->members()->attach($member, ['role' => TeamRole::Owner->value]);
    $member->switchTeam($workspace);

    config(['rentier.platform_admin_emails' => [$admin->email]]);

    $this->actingAs($admin)
        ->patch('http://admin.localhost/workspaces/portofoliu-suspendabil/suspend', [
            'reason' => 'Verificare workspace.',
        ])
        ->assertRedirect();

    $workspace->refresh();

    expect($workspace->isSuspended())->toBeTrue()
        ->and($workspace->suspension_reason)->toBe('Verificare workspace.')
        ->and($workspace->suspended_by_user_id)->toBe($admin->id)
        ->and($member->fresh()->switchTeam($workspace))->toBeFalse();

    $this->actingAs($member)
        ->get('http://localhost/portofoliu-suspendabil/dashboard')
        ->assertStatus(423);

    $this->actingAs($admin)
        ->patch('http://admin.localhost/workspaces/portofoliu-suspendabil/reactivate')
        ->assertRedirect();

    expect($workspace->fresh()->isSuspended())->toBeFalse()
        ->and($workspace->fresh()->reactivated_by_user_id)->toBe($admin->id);
});

test('suspension actions are not exposed on the normal application host', function () {
    $admin = User::factory()->create(['email' => 'operator@example.com']);
    $target = User::factory()->create();
    $workspace = $target->currentTeam;

    config(['rentier.platform_admin_emails' => [$admin->email]]);

    $this->actingAs($admin)
        ->patch("http://localhost/users/{$target->id}/suspend", ['reason' => 'Test'])
        ->assertNotFound();

    $this->actingAs($admin)
        ->patch("http://localhost/workspaces/{$workspace->slug}/suspend", ['reason' => 'Test'])
        ->assertNotFound();
});
