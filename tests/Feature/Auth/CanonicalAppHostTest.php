<?php

use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    config(['app.url' => 'https://app.rentier.ro']);
});

test('apex login navigation redirects to the canonical app host and preserves invitation context', function () {
    $response = $this->get('https://rentier.ro/login?invitation=sample-code');

    $response->assertRedirect('https://app.rentier.ro/login?invitation=sample-code');
});

test('direct apex login submission is not processed on the public host', function () {
    $response = $this->post('https://rentier.ro/login', [
        'email' => 'owner@example.com',
        'password' => 'not-submitted-on-apex',
    ]);

    $response->assertStatus(303);
    $response->assertRedirect('https://app.rentier.ro/login');
    $this->assertGuest();
});

test('apex workspace navigation redirects to the canonical app host before authentication', function () {
    $response = $this->get('https://rentier.ro/demo-workspace/dashboard');

    $response->assertRedirect('https://app.rentier.ro/demo-workspace/dashboard');
});

test('canonical app login still renders normally', function () {
    $this->get('https://app.rentier.ro/login')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('auth/login'));
});

test('public apex root remains the landing page', function () {
    $this->get('https://rentier.ro/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('welcome'));
});

test('legacy apex reset links remain available during the signed link transition window', function () {
    $this->get('https://rentier.ro/reset-password/sample-token?email=owner%40example.com')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('auth/reset-password')
            ->where('token', 'sample-token')
            ->where('email', 'owner@example.com'));
});
