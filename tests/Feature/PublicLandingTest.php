<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('public landing exposes the canonical application URL', function () {
    config(['app.url' => 'https://app.rentier.ro']);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('welcome')
            ->where('appUrl', 'https://app.rentier.ro'));
});

test('public landing describes only implemented landlord workflows', function () {
    $source = file_get_contents(resource_path('js/pages/welcome.tsx'));

    expect($source)
        ->toContain('Proprietăți')
        ->toContain('Contracte')
        ->toContain('Plăți')
        ->toContain('Cheltuieli și decontări')
        ->toContain('Rentier este în beta privată.')
        ->toContain('app.rentier.ro');
});


test('public apex root renders the landing while app root sends guests to login', function () {
    config(['app.url' => 'https://app.rentier.ro']);

    $this->get('https://rentier.ro/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('welcome'));

    $this->get('https://app.rentier.ro/')
        ->assertRedirect('https://app.rentier.ro/login');
});


test('authenticated app root enters the current workspace dashboard', function () {
    config(['app.url' => 'https://app.rentier.ro']);

    $user = User::factory()->create();
    $team = $user->currentTeam;

    $this->actingAs($user)
        ->get('https://app.rentier.ro/')
        ->assertRedirect(route('dashboard', ['current_team' => $team->slug]));
});
