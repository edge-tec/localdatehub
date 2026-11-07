<?php

namespace App\Http\Middleware;

use App\Support\Exceptions\AgeVerificationException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyAgeMiddleware
{
    /**
     * Ensure the user has confirmed 18+ age status before accessing adult dating sections.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check session or user confirmation
        $confirmed = $request->session()->get('age_confirmed_18', false);

        if (!$confirmed && $request->user()) {
            $confirmed = (bool) $request->user()->is_age_verified;
        }

        if (!$confirmed && !$request->is('age-confirmation*')) {
            if ($request->expectsJson() || $request->is('api/*')) {
                throw new AgeVerificationException();
            }

            return redirect()->route('age.gate');
        }

        return $next($request);
    }
}
