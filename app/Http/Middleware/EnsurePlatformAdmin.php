<?php

namespace App\Http\Middleware;

use App\Support\PlatformAdmin;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlatformAdmin
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response|RedirectResponse
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->guest('/login');
        }

        abort_unless(PlatformAdmin::allows($user), 403);

        if (! $user->hasVerifiedEmail()) {
            return redirect('/email/verify');
        }

        return $next($request);
    }
}
