<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('admin host redirects guests to same host login', function () {
    $this->get('http://admin.localhost/')
        ->assertRedirect('http://admin.localhost/login');
});

test('normal users cannot access the platform admin', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('http://admin.localhost/')
        ->assertForbidden();
});

test('configured verified platform admins can access the admin dashboard', function () {
    $admin = User::factory()->create();

    config(['rentier.platform_admin_emails' => [$admin->email]]);

    $this->actingAs($admin)
        ->get('http://admin.localhost/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/dashboard')
            ->where('stats.users', 1)
            ->where('stats.workspaces', 1)
            ->where('stats.properties', 0)
            ->where('stats.leases', 0)
        );
});

test('platform admin password login stays on the admin host', function () {
    $admin = User::factory()->create([
        'email' => 'platform-admin@example.com',
    ]);

    config(['rentier.platform_admin_emails' => [$admin->email]]);

    $this->post('http://admin.localhost/login', [
        'email' => $admin->email,
        'password' => 'password',
    ])->assertRedirect('http://admin.localhost/');
});

test('normal landlord routes are not served from the admin host', function () {
    $admin = User::factory()->create();
    $team = $admin->currentTeam;

    config(['rentier.platform_admin_emails' => [$admin->email]]);

    $this->actingAs($admin)
        ->get("http://admin.localhost/{$team->slug}/dashboard")
        ->assertRedirect('http://admin.localhost');
});
