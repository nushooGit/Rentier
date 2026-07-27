<?php

use Illuminate\Support\Facades\App;

test('Romanian is the default application locale', function () {
    expect(config('app.locale'))->toBe('ro')
        ->and(config('app.fallback_locale'))->toBe('en');
});

test('authentication and validation messages resolve through the active locale', function () {
    App::setLocale('ro');

    expect(__('auth.failed'))->toBe('Datele de autentificare nu sunt corecte.')
        ->and(__('validation.custom.property.name.required'))->toBe('Numele proprietății este obligatoriu.')
        ->and(__('validation.custom.lease.end_date.after_or_equal'))->toBe('Data de sfârșit trebuie să fie egală sau ulterioară datei de început.');

    App::setLocale('en');

    expect(__('auth.failed'))->toBe('These credentials do not match our records.')
        ->and(__('validation.custom.property.name.required'))->toBe('The property name is required.')
        ->and(__('validation.custom.lease.end_date.after_or_equal'))->toBe('The end date must be equal to or later than the start date.');

    App::setLocale('ro');
});
