<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsActive
{
    /**
     * Handle an incoming request.
     * Ensure the user's account is active and not suspended or banned.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->account_status === 'suspended') {
            if ($request->routeIs('account.suspended') || $request->routeIs('logout')) {
                return $next($request);
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your account has been suspended for community policy violations.',
                    'error_code' => 'ACCOUNT_SUSPENDED',
                ], 403);
            }

            return redirect()->route('account.suspended');
        }

        return $next($request);
    }
}
