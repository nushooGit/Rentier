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
        ->and(__('validation.custom.lease.start_date.required'))->toBe('Data de început este obligatorie.')
        ->and(__('validation.custom.lease.start_date.date'))->toBe('Data de început trebuie să fie o dată validă.')
        ->and(__('validation.custom.lease.end_date.date'))->toBe('Data de sfârșit trebuie să fie o dată validă.')
        ->and(__('validation.custom.lease.end_date.after_or_equal'))->toBe('Data de sfârșit trebuie să fie egală sau ulterioară datei de început.');

    App::setLocale('en');

    expect(__('auth.failed'))->toBe('These credentials do not match our records.')
        ->and(__('validation.custom.property.name.required'))->toBe('The property name is required.')
        ->and(__('validation.custom.lease.start_date.required'))->toBe('The start date is required.')
        ->and(__('validation.custom.lease.start_date.date'))->toBe('The start date must be a valid date.')
        ->and(__('validation.custom.lease.end_date.date'))->toBe('The end date must be a valid date.')
        ->and(__('validation.custom.lease.end_date.after_or_equal'))->toBe('The end date must be equal to or later than the start date.');

    App::setLocale('ro');
});
