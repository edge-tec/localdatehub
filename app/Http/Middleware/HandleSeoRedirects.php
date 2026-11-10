<?php

namespace App\Http\Middleware;

use App\Modules\SEO\Models\SeoRedirect;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class HandleSeoRedirects
{
    /**
     * Handle an incoming request and check for configured 301/302 redirects or 410 Gone status.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Don't intercept non-GET, internal, health, or debug requests
        if (! $request->isMethod('GET')
            || $request->is('livewire/*')
            || $request->is('_debugbar/*')
            || $request->is('health*')
            || $request->is('up')
        ) {
            return $next($request);
        }

        try {
            if (! Schema::hasTable('seo_redirects')) {
                return $next($request);
            }

            $rawPath = '/' . ltrim($request->path(), '/');

            $redirect = SeoRedirect::where('is_active', true)
                ->where(function ($query) use ($rawPath) {
                    $query->where('source_path', $rawPath)
                        ->orWhere('source_path', ltrim($rawPath, '/'));
                })
                ->first();

            if ($redirect) {
                $redirect->increment('hits');
                $redirect->update(['last_hit_at' => now()]);

                if ($redirect->status_code === 410) {
                    return response('410 Gone - This resource has been permanently removed.', 410, [
                        'Content-Type' => 'text/plain; charset=utf-8',
                    ]);
                }

                if ($redirect->target_url && in_array($redirect->status_code, [301, 302, 307, 308])) {
                    $target = trim($redirect->target_url);

                    // Open Redirect & SSRF Defense: Block protocol-relative or dangerous schemes
                    if (str_starts_with($target, '//') || str_starts_with(strtolower($target), 'javascript:') || str_starts_with(strtolower($target), 'data:')) {
                        return $next($request);
                    }

                    return redirect($target, $redirect->status_code);
                }
            }
        } catch (\Throwable) {
            // Ignore DB errors during testing/boot
        }

        return $next($request);
    }
}
