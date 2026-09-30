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
        $cookieLocale = $request->cookie('rentier_locale');
        $configuredLocale = config('app.locale');

        $locale = is_string($cookieLocale)
            ? $cookieLocale
            : (is_string($configuredLocale) ? $configuredLocale : 'ro');

        if (! in_array($locale, self::SUPPORTED_LOCALES, true)) {
            $locale = is_string($configuredLocale) ? $configuredLocale : 'ro';
        }

        App::setLocale($locale);

        return $next($request);
    }
}
