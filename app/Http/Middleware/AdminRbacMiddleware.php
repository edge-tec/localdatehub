<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminRbacMiddleware
{
    /**
     * Handle an incoming request and enforce granular RBAC permissions.
     */
    public function handle(Request $request, Closure $next, ?string $permission = null): Response
    {
        if (!Auth::check()) {
            return redirect()->guest(route('login'));
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Check account suspension
        if ($user->isSuspended()) {
            return redirect()->route('account.suspended');
        }

        // Verify the user is an administrative staff member
        if (!$user->isStaff()) {
            abort(403, 'Access Denied: Administrative privileges required.');
        }

        // Verify granular permission if specified
        if ($permission && !$user->hasPermission($permission)) {
            abort(403, "Access Denied: Your staff role lacks the required [{$permission}] permission.");
        }

        return $next($request);
    }
}

