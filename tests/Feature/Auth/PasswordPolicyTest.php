<?php

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

test('production password policy rejects passwords missing any required element', function () {
    $originalEnvironment = app()->environment();
    app()->detectEnvironment(fn () => 'production');

    try {
        foreach ([
            'Aa1!abcde',   // Fewer than 10 characters.
            'abcdefgh1!', // No uppercase letter.
            'ABCDEFGH1!', // No lowercase letter.
            'Abcdefghi!', // No digit.
            'Abcdefghi1', // No special character.
        ] as $candidate) {
            $validator = Validator::make(
                ['password' => $candidate],
                ['password' => ['required', 'string', Password::default()]],
            );

            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('password'))->toBeTrue();
        }
    } finally {
        app()->detectEnvironment(fn () => $originalEnvironment);
    }
});

test('Romanian password validation messages resolve without raw keys', function () {
    expect(__('validation.min.string', ['attribute' => 'parola', 'min' => 10]))
        ->toBe('Câmpul parola trebuie să conțină cel puțin 10 caractere.')
        ->and(__('validation.password.mixed', ['attribute' => 'parola']))
        ->toContain('litere mari și mici')
        ->and(__('validation.password.numbers', ['attribute' => 'parola']))
        ->toContain('cifră')
        ->and(__('validation.password.symbols', ['attribute' => 'parola']))
        ->toContain('caracter special');
});

test('notification mail footer resolves in Romanian', function () {
    $fallback = "If you're having trouble clicking the \":actionText\" button, copy and paste the URL below\ninto your web browser:";

    expect(__('Regards'))->toBe('Cu respect')
        ->and(__($fallback, ['actionText' => 'Resetează parola']))
        ->toStartWith('Dacă butonul');
});
