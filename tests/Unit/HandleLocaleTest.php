<?php

use App\Http\Middleware\HandleLocale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

afterEach(function () {
    App::setLocale('ro');
});

test('locale middleware uses a supported locale cookie', function () {
    $request = Request::create('/login');
    $request->cookies->set('rentier_locale', 'en');

    $response = app(HandleLocale::class)->handle(
        $request,
        function (): Response {
            expect(App::getLocale())->toBe('en');

            return response()->noContent();
        },
    );

    expect($response->getStatusCode())->toBe(204);
});

test('locale middleware falls back to Romanian for an unsupported locale cookie', function () {
    $request = Request::create('/login');
    $request->cookies->set('rentier_locale', 'de');

    app(HandleLocale::class)->handle(
        $request,
        function (): Response {
            expect(App::getLocale())->toBe('ro');

            return response()->noContent();
        },
    );
});
