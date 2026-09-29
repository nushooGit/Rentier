<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectPublicHostToApp
{
    public function handle(Request $request, Closure $next): Response|RedirectResponse
    {
        $appUrl = rtrim((string) config('app.url'), '/');
        $appHost = parse_url($appUrl, PHP_URL_HOST);

        if (! is_string($appHost) || ! str_starts_with($appHost, 'app.')) {
            return $next($request);
        }

        $publicHost = substr($appHost, 4);

        if ($request->getHost() !== $publicHost || $request->getPathInfo() === '/') {
            return $next($request);
        }

        return redirect()->away($appUrl.$request->getRequestUri(), 307);
    }
}
