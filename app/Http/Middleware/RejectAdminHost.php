<?php

namespace App\Http\Middleware;

use App\Support\PlatformAdmin;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RejectAdminHost
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! PlatformAdmin::isAdminHost($request)) {
            return $next($request);
        }

        $adminUrl = PlatformAdmin::adminUrl();

        abort_if($adminUrl === null, 404);

        return redirect()->away($adminUrl);
    }
}
