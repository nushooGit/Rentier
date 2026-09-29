<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceCanonicalAppHost
{
    public function handle(Request $request, Closure $next): Response
    {
        $appUrl = rtrim((string) config('app.url'), '/');
        $appHost = parse_url($appUrl, PHP_URL_HOST);

        if (! is_string($appHost) || ! str_starts_with($appHost, 'app.')) {
            return $next($request);
        }

        if ($request->getHost() === $appHost) {
            return $next($request);
        }

        $publicHost = substr($appHost, 4);

        if ($request->getHost() !== $publicHost) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();

        if ($routeName === null || in_array($routeName, ['home', 'verification.verify'], true)) {
            return $next($request);
        }

        if ($request->isMethod('GET') || $request->isMethod('HEAD')) {
            return $this->redirectToCanonicalApp($request, $appUrl);
        }

        return redirect()->away($appUrl.'/', Response::HTTP_SEE_OTHER);
    }

    private function redirectToCanonicalApp(Request $request, string $appUrl): RedirectResponse
    {
        return redirect()->away($appUrl.$request->getRequestUri(), Response::HTTP_FOUND);
    }
}
