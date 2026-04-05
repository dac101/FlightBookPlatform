<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasSeenTutorial
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (
            $request->user()
            && ! $request->user()->seen_tutorial_at
            && ! $request->routeIs('help.*', 'tutorial.seen', 'logout', 'profile.*')
        ) {
            return redirect()->route('help.index');
        }

        return $next($request);
    }
}
