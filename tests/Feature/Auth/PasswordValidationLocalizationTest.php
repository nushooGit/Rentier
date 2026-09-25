<?php

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

test('short passwords show a Romanian minimum length error', function () {
    app()->setLocale('ro');

    $validator = Validator::make(
        ['password' => 'Parola1!'],
        ['password' => ['required', 'string', 'min:12']],
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->first('password'))
        ->toBe('Câmpul parolă trebuie să aibă cel puțin 12 caractere.');
});

test('password composition errors are translated into Romanian', function () {
    app()->setLocale('ro');

    $validator = Validator::make(
        ['password' => 'doarliterefaramajuscule'],
        ['password' => [Password::min(12)->mixedCase()->numbers()->symbols()]],
    );

    expect($validator->fails())->toBeTrue();

    $messages = $validator->errors()->get('password');

    expect($messages)->toContain('Parola trebuie să conțină cel puțin o literă mare și una mică.')
        ->toContain('Parola trebuie să conțină cel puțin o cifră.')
        ->toContain('Parola trebuie să conțină cel puțin un simbol.');
});

test('password confirmation errors use Romanian copy', function () {
    app()->setLocale('ro');

    $validator = Validator::make(
        ['password' => 'Parola123!Sigura', 'password_confirmation' => 'alta-parola'],
        ['password' => ['required', 'confirmed']],
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->first('password'))
        ->toBe('Confirmarea câmpului parolă nu coincide.');
});
