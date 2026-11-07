<?php

namespace App\Http\Middleware;

use App\Support\Exceptions\AgeVerificationException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAgeAssurance
{
    /**
     * Handle an incoming request.
     * Ensure the user or guest has validated 18+ age status before viewing adult classifieds.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        $isVerified = false;

        if ($user) {
            $isVerified = (bool) $user->is_age_verified;
        } else {
            $isVerified = (bool) $request->session()->get('age_confirmed_18', false);
        }

        if (!$isVerified && !$request->routeIs('age.gate') && !$request->routeIs('age.confirm')) {
            if ($request->expectsJson() || $request->is('api/*')) {
                throw new AgeVerificationException('Access restricted to verified adults aged 18 or older.');
            }

            return redirect()->route('age.gate');
        }

        return $next($request);
    }
}

