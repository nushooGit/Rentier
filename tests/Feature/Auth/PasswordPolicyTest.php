<?php

use Illuminate\Contracts\Validation\UncompromisedVerifier;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

test('production password policy rejects each missing element with Romanian errors', function () {
    $originalEnvironment = app()->environment();
    app()->detectEnvironment(fn () => 'production');

    try {
        foreach ([
            ['Aa1!abcde', 'cel puțin 10 caractere'], // Nine characters.
            ['abcdefgh1!', 'litere mari și mici'], // No uppercase letter.
            ['ABCDEFGH1!', 'litere mari și mici'], // No lowercase letter.
            ['Abcdefghi!', 'o cifră'], // No digit.
            ['Abcdefghi1', 'caracter special'], // No symbol.
        ] as [$candidate, $message]) {
            $validator = Validator::make(
                ['password' => $candidate],
                ['password' => ['required', 'string', Password::default()]],
            );

            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->first('password'))->toContain($message);
        }
    } finally {
        app()->detectEnvironment(fn () => $originalEnvironment);
    }
});

test('production password policy accepts exactly ten valid uncompromised characters', function () {
    $originalEnvironment = app()->environment();
    app()->detectEnvironment(fn () => 'production');

    $verifier = Mockery::mock(UncompromisedVerifier::class);
    $verifier->shouldReceive('verify')->once()->andReturn(true);
    app()->instance(UncompromisedVerifier::class, $verifier);

    try {
        $validator = Validator::make(
            ['password' => 'Aa1!abcdef'],
            ['password' => ['required', 'string', Password::default()]],
        );

        expect($validator->passes())->toBeTrue();
    } finally {
        app()->detectEnvironment(fn () => $originalEnvironment);
    }
});

test('production password policy keeps compromised password screening', function () {
    $originalEnvironment = app()->environment();
    app()->detectEnvironment(fn () => 'production');

    $verifier = Mockery::mock(UncompromisedVerifier::class);
    $verifier->shouldReceive('verify')->once()->andReturn(false);
    app()->instance(UncompromisedVerifier::class, $verifier);

    try {
        $validator = Validator::make(
            ['password' => 'Aa1!abcdef'],
            ['password' => ['required', 'string', Password::default()]],
        );

        expect($validator->fails())->toBeTrue();
        expect($validator->errors()->first('password'))
            ->toContain('breșă de securitate');
    } finally {
        app()->detectEnvironment(fn () => $originalEnvironment);
    }
});

test('Romanian password validation messages resolve without raw keys', function () {
    expect(__('validation.min.string', ['attribute' => 'parola', 'min' => 10]))
        ->toBe('Câmpul parola trebuie să conțină cel puțin 10 caractere.')
        ->and(__('validation.password.letters', ['attribute' => 'parola']))
        ->toContain('o literă')
        ->and(__('validation.password.mixed', ['attribute' => 'parola']))
        ->toContain('litere mari și mici')
        ->and(__('validation.password.numbers', ['attribute' => 'parola']))
        ->toContain('cifră')
        ->and(__('validation.password.symbols', ['attribute' => 'parola']))
        ->toContain('caracter special')
        ->and(__('validation.password.uncompromised', ['attribute' => 'parola']))
        ->toContain('breșă de securitate');
});

test('notification mail footer resolves in Romanian including the salutation comma', function () {
    $fallback = "If you're having trouble clicking the \":actionText\" button, copy and paste the URL below\ninto your web browser:";

    expect(__('Regards'))->toBe('Cu respect')
        ->and(__('Regards,'))->toBe('Cu respect,')
        ->and(__($fallback, ['actionText' => 'Resetează parola']))
        ->toContain('Dacă butonul "Resetează parola" nu funcționează');
});
