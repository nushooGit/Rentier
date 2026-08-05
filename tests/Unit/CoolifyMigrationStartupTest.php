<?php

test('coolify startup invokes controlled migrations before supervisor process startup', function () {
    $startScript = file_get_contents(base_path('deploy/coolify/start.sh'));
    $migrationPosition = strpos($startScript, 'run-migrations.sh');

    expect($startScript)->toContain('/app/deploy/coolify/run-migrations.sh')
        ->and($migrationPosition)
        ->toBeLessThan(strpos($startScript, 'starting supervisor'))
        ->and($migrationPosition)
        ->toBeLessThan(strpos($startScript, 'exec "$SUPERVISORD_BIN"'));
});

test('controlled migration script keeps automatic migrations opt in and production only', function () {
    $migrationScript = file_get_contents(base_path('deploy/coolify/run-migrations.sh'));

    expect($migrationScript)
        ->toContain('RENTIER_AUTO_MIGRATE')
        ->toContain('automatic migrations are disabled')
        ->toContain('APP_ENV=production')
        ->toContain('exit 0');
});

test('controlled migration script fails startup when readiness or migrations fail', function () {
    $migrationScript = file_get_contents(base_path('deploy/coolify/run-migrations.sh'));
    $startScript = file_get_contents(base_path('deploy/coolify/start.sh'));

    expect($migrationScript)
        ->toContain('database was not reachable before timeout')
        ->toContain('database migration failed')
        ->toContain('exit 1')
        ->and($startScript)
        ->toContain('controlled migration prestart failed');
});

test('controlled migration script uses only forward forced migrations', function () {
    $deploymentSources = collect([
        file_get_contents(base_path('deploy/coolify/start.sh')),
        file_get_contents(base_path('deploy/coolify/run-migrations.sh')),
    ])->implode("\n");

    expect($deploymentSources)
        ->toContain('migrate --force --no-interaction')
        ->not->toContain('migrate:fresh')
        ->not->toContain('migrate:reset')
        ->not->toContain('migrate:refresh')
        ->not->toContain('migrate:rollback')
        ->not->toContain('db:wipe');
});
