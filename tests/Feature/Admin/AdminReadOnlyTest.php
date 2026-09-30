<?php

use App\Models\Property;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('platform admin can inspect users without workspace role granting admin access', function () {
    $admin = User::factory()->create([
        'name' => 'Platform Operator',
        'email' => 'operator@example.com',
    ]);
    $landlord = User::factory()->create([
        'name' => 'Landlord Example',
        'email' => 'landlord@example.com',
    ]);

    config(['rentier.platform_admin_emails' => [$admin->email]]);

    $this->actingAs($admin)
        ->get('http://admin.localhost/users')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/users/index')
            ->has('users', 2)
            ->where('users.0.id', $landlord->id)
            ->where('users.0.is_platform_admin', false)
            ->where('users.1.id', $admin->id)
            ->where('users.1.is_platform_admin', true)
        );
});

test('platform admin can inspect workspace counts read only', function () {
    $admin = User::factory()->create();
    $workspace = Team::factory()->create([
        'name' => 'Portfolio București',
    ]);
    $workspace->members()->attach($admin, ['role' => 'owner']);
    Property::factory()->for($workspace)->create();

    config(['rentier.platform_admin_emails' => [$admin->email]]);

    $this->actingAs($admin)
        ->get('http://admin.localhost/workspaces')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/workspaces/index')
            ->has('workspaces', 2)
            ->where('workspaces.0.id', $workspace->id)
            ->where('workspaces.0.members_count', 1)
            ->where('workspaces.0.properties_count', 1)
        );
});

test('admin list routes do not exist on the normal application host', function () {
    $admin = User::factory()->create();

    config(['rentier.platform_admin_emails' => [$admin->email]]);

    $this->actingAs($admin)
        ->get('http://localhost/users')
        ->assertNotFound();

    $this->actingAs($admin)
        ->get('http://localhost/workspaces')
        ->assertNotFound();
});
