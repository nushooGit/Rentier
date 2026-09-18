<?php

test('tracked environment defaults use uncoupled database sessions', function () {
    $environment = file_get_contents(base_path('.env.example'));

    expect($environment)
        ->toMatch('/^SESSION_DRIVER=database$/m')
        ->toMatch('/^SESSION_CONNECTION=null$/m')
        ->toMatch('/^SESSION_STORE=null$/m')
        ->toMatch('/^SESSION_DOMAIN=null$/m');
});

test('production guidance uses secure host only database sessions', function (string $path) {
    $guidance = file_get_contents(base_path($path));

    expect($guidance)
        ->toContain('SESSION_DRIVER=database')
        ->toContain('SESSION_CONNECTION=null')
        ->toContain('SESSION_STORE=null')
        ->toContain('SESSION_DOMAIN=null')
        ->toContain('SESSION_SECURE_COOKIE=true');
})->with([
    'beta deployment checklist' => 'docs/deployment-beta.md',
    'Coolify deployment runbook' => 'docs/hetzner-deployment.md',
]);

test('test sessions keep optional connection store and domain selectors null', function () {
    expect(config('session.driver'))->toBe('array')
        ->and(config('session.connection'))->toBeNull()
        ->and(config('session.store'))->toBeNull()
        ->and(config('session.domain'))->toBeNull();
});
