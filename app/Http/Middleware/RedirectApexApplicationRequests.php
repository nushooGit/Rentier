<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectApexApplicationRequests
{
    /**
     * Redirect browser navigation from the public apex host to the canonical
     * application host, while leaving legacy signed/reset links untouched.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $appUrl = rtrim((string) config('app.url'), '/');
        $appHost = parse_url($appUrl, PHP_URL_HOST);

        if (! is_string($appHost) || ! str_starts_with($appHost, 'app.')) {
            return $next($request);
        }

        $apexHost = substr($appHost, 4);

        if ($request->getHost() !== $apexHost) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();

        if ($request->isMethod('GET') || $request->isMethod('HEAD')) {
            if ($this->isCanonicalAppNavigation($routeName, $request)) {
                return redirect()->away($appUrl.$request->getRequestUri());
            }

            return $next($request);
        }

        if ($routeName === 'login.store') {
            return redirect()->away($appUrl.'/login', 303);
        }

        return $next($request);
    }

    private function isCanonicalAppNavigation(?string $routeName, Request $request): bool
    {
        if ($request->is('settings') || $routeName === 'dashboard') {
            return true;
        }

        if (in_array($routeName, [
            'login',
            'register',
            'password.request',
            'password.confirm',
            'two-factor.login',
            'verification.notice',
        ], true)) {
            return true;
        }

        foreach ([
            'properties.',
            'leases.',
            'payments.',
            'expenses.',
            'profile.',
            'security.',
            'appearance.',
            'teams.',
        ] as $prefix) {
            if (is_string($routeName) && str_starts_with($routeName, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
