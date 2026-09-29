<?php

use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    config(['app.url' => 'https://app.rentier.ro']);
});

test('public apex root remains the landing page', function () {
    $this->get('https://rentier.ro/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('welcome'));
});

test('apex login redirects to the canonical app host', function () {
    $this->get('https://rentier.ro/login')
        ->assertStatus(307)
        ->assertHeader('Location', 'https://app.rentier.ro/login');
});

test('apex application routes redirect to the same path on the app host', function () {
    $this->get('https://rentier.ro/rentier/dashboard')
        ->assertStatus(307)
        ->assertHeader('Location', 'https://app.rentier.ro/rentier/dashboard');
});

test('apex token and invitation links preserve their path and query string', function () {
    $this->get('https://rentier.ro/reset-password/sample-token?email=user%40example.com')
        ->assertStatus(307)
        ->assertHeader('Location', 'https://app.rentier.ro/reset-password/sample-token?email=user%40example.com');

    $this->get('https://rentier.ro/login?invitation=sample-code')
        ->assertStatus(307)
        ->assertHeader('Location', 'https://app.rentier.ro/login?invitation=sample-code');
});

test('direct apex login submissions are redirected before authentication', function () {
    $this->post('https://rentier.ro/login', [
        'email' => 'nobody@example.com',
        'password' => 'not-a-real-password',
    ])
        ->assertStatus(307)
        ->assertHeader('Location', 'https://app.rentier.ro/login');
});

test('canonical app host keeps authentication routes available', function () {
    $this->get('https://app.rentier.ro/login')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('auth/login'));
});
