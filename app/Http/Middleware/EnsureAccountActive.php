<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountActive
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response|RedirectResponse|JsonResponse
    {
        $user = $request->user();

        if (! $user?->isSuspended()) {
            return $next($request);
        }

        Auth::guard()->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Contul Rentier este suspendat.',
            ], 423);
        }

        return redirect()
            ->route('login')
            ->withErrors([
                'email' => 'Contul tău Rentier este suspendat. Contactează suportul dacă ai nevoie de ajutor.',
            ]);
    }
}
