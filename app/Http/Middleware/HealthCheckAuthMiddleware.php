<?php

namespace App\Http\Middleware;

use App\Support\Responses\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HealthCheckAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $expectedKey = config('localdatehub.health_check.secret_key');
        $providedKey = $request->header('X-Health-Key') ?? $request->query('key');

        if (empty($expectedKey) || !is_string($providedKey) || !hash_equals((string) $expectedKey, $providedKey)) {
            return ApiResponse::forbidden('Access to detailed health metrics requires a valid authorization key.');
        }

        return $next($request);
    }
}
