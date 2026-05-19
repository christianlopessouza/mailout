<?php

namespace App\Http\Master\Middleware;

use Closure;
use Illuminate\Http\Request;

class AuthInternalMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $configuredToken = config('services.internal_api_token');
        $requestToken = $request->bearerToken() ?: $request->header('X-Internal-Token');

        if (!$configuredToken || !$requestToken || !hash_equals($configuredToken, $requestToken)) {
            return response()->json(['message' => 'Invalid internal token'], 401);
        }

        return $next($request);
    }
}
