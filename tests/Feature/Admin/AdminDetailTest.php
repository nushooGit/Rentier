<?php

use App\Enums\TeamRole;
use App\Models\Property;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('platform admin can inspect a user and workspace memberships read only', function () {
    $admin = User::factory()->create([
        'email' => 'operator@example.com',
    ]);
    $user = User::factory()->create([
        'name' => 'Landlord Example',
        'email' => 'landlord@example.com',
    ]);
    $workspace = Team::factory()->create([
        'name' => 'Portofoliu Nord',
    ]);
    $workspace->members()->attach($user, [
        'role' => TeamRole::Admin->value,
    ]);

    config(['rentier.platform_admin_emails' => [$admin->email]]);

    $this->actingAs($admin)
        ->get("http://admin.localhost/users/{$user->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/users/show')
            ->where('user.id', $user->id)
            ->where('user.email', 'landlord@example.com')
            ->where('user.is_platform_admin', false)
            ->where('stats.workspaces', 2)
            ->where('stats.owned_workspaces', 1)
            ->has('memberships', 2)
        );
});

test('platform admin can inspect workspace members and operational counts read only', function () {
    $admin = User::factory()->create([
        'email' => 'operator@example.com',
    ]);
    $workspace = Team::factory()->create([
        'name' => 'Portofoliu București',
        'slug' => 'portofoliu-bucuresti',
    ]);
    $workspace->members()->attach($admin, [
        'role' => TeamRole::Owner->value,
    ]);
    Property::factory()->for($workspace)->create([
        'name' => 'Apartament Central',
    ]);

    config(['rentier.platform_admin_emails' => [$admin->email]]);

    $this->actingAs($admin)
        ->get('http://admin.localhost/workspaces/portofoliu-bucuresti')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/workspaces/show')
            ->where('workspace.id', $workspace->id)
            ->where('stats.members', 1)
            ->where('stats.properties', 1)
            ->where('stats.leases', 0)
            ->has('members', 1)
            ->has('properties', 1)
            ->where('properties.0.name', 'Apartament Central')
        );
});

test('admin detail routes remain unavailable on the normal application host', function () {
    $admin = User::factory()->create();
    $workspace = $admin->currentTeam;

    config(['rentier.platform_admin_emails' => [$admin->email]]);

    $this->actingAs($admin)
        ->get("http://localhost/users/{$admin->id}")
        ->assertNotFound();

    $this->actingAs($admin)
        ->get("http://localhost/workspaces/{$workspace->slug}")
        ->assertNotFound();
});
