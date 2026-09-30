<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class HandleLocale
{
    /**
     * @var list<string>
     */
    private const SUPPORTED_LOCALES = ['ro', 'en'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = (string) $request->cookie('rentier_locale', config('app.locale'));

        if (! in_array($locale, self::SUPPORTED_LOCALES, true)) {
            $locale = (string) config('app.locale', 'ro');
        }

        App::setLocale($locale);

        return $next($request);
    }
}
