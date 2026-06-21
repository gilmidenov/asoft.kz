<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthOptional
{
    public function handle(Request $request, Closure $next): mixed
    {
        // If a Bearer token is present, resolve the user via Sanctum.
        // Unlike auth:sanctum, this does not abort with 401 when unauthenticated.
        if ($request->bearerToken()) {
            Auth::shouldUse('sanctum');
        }

        return $next($request);
    }
}
