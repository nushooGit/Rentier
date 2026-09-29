<?php

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
