<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeadersMiddleware
{
    /**
     * Handle an incoming request and attach enterprise-grade security headers.
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        // 1. Prevent Clickjacking
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // 2. Prevent MIME type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // 3. Prevent cross-site scripting (XSS) filter
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // 4. Strict Referrer Policy
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // 5. Cross-Domain & Window Isolation Policies
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin-allow-popups');

        // 6. Content Security Policy (strict structural directives enforced + full policy in Report-Only)
        $response->headers->set(
            'Content-Security-Policy',
            "frame-ancestors 'self'; base-uri 'self'; object-src 'none'; form-action 'self';"
        );

        $reportOnlyPolicy = implode('; ', [
            "default-src 'self'",
            "base-uri 'self'",
            "object-src 'none'",
            "frame-ancestors 'self'",
            "form-action 'self'",
            "script-src 'self' https://challenges.cloudflare.com",
            "style-src 'self' https://fonts.googleapis.com",
            "font-src 'self' https://fonts.gstatic.com data:",
            "img-src 'self' data: https:",
            "connect-src 'self' https://challenges.cloudflare.com",
            "frame-src 'self' https://challenges.cloudflare.com",
        ]) . ';';

        $response->headers->set('Content-Security-Policy-Report-Only', $reportOnlyPolicy);

        // 7. Restrict sensitive browser APIs & hardware features
        $response->headers->set(
            'Permissions-Policy',
            'camera=(), microphone=(), payment=(self), geolocation=(self), usb=(), accelerometer=(), gyroscope=()'
        );

        // 8. Strip Server Fingerprinting Headers
        $response->headers->remove('X-Powered-By');
        if (function_exists('header_remove')) {
            @header_remove('X-Powered-By');
        }

        // 9. Strict Transport Security (HSTS) when on HTTPS or production
        if ($request->isSecure() || app()->environment('production')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        // 10. X-Robots-Tag & strict no-store Cache-Control for private, admin, auth, and API areas
        if ($request->is('account*', 'admin*', 'checkout*', 'password*', 'email*', 'api*', 'webhooks*', 'login', 'register')) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow, noarchive');
            $response->headers->set('Cache-Control', 'private, no-store, no-cache, must-revalidate, max-age=0');
        }

        return $response;
    }
}

