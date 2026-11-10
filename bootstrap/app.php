<?php

use App\Http\Middleware\HealthCheckAuthMiddleware;
use App\Http\Middleware\VerifyAgeMiddleware;
use App\Support\Exceptions\DomainException;
use App\Support\Responses\ApiResponse;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        \App\Modules\Locations\Commands\ImportLocationsCommand::class,
        \App\Modules\Locations\Commands\ValidateLocationsCommand::class,
        \App\Console\Commands\ProcessRenewalsCommand::class,
        \App\Console\Commands\ExpirePromotionsCommand::class,
        \App\Console\Commands\ReconcileTransactionsCommand::class,
        \App\Console\Commands\NotifyExpiringListingsCommand::class,
        \App\Console\Commands\ExpireListingBoostsCommand::class,
        \App\Console\Commands\CheckPendingCryptoPaymentsCommand::class,
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(\App\Http\Middleware\HandleSeoRedirects::class);

        $middleware->web(append: [
            \App\Http\Middleware\SecurityHeadersMiddleware::class,
            \App\Http\Middleware\TrackPlatformTraffic::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'webhooks/*',
            'api/*',
        ]);

        $middleware->alias([
            'health.auth' => HealthCheckAuthMiddleware::class,
            'age.verify' => VerifyAgeMiddleware::class,
            'active' => \App\Http\Middleware\EnsureAccountIsActive::class,
            'age.assured' => \App\Http\Middleware\EnsureAgeAssurance::class,
            'admin.rbac' => \App\Http\Middleware\AdminRbacMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Render DomainException into standard ApiResponse format
        $exceptions->render(function (DomainException $e, Request $request) {
            return $e->render($request);
        });

        // Custom API exception rendering
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Structured logging for unhandled exceptions with request context
        $exceptions->report(function (Throwable $e) {
            if (app()->bound('request')) {
                $request = request();
                Log::channel('single')->error('Unhandled Exception occurred: ' . $e->getMessage(), [
                    'url' => $request?->fullUrl(),
                    'method' => $request?->method(),
                    'ip' => $request?->ip(),
                    'exception' => get_class($e),
                    'file' => $e->getFile() . ':' . $e->getLine(),
                ]);
            }
        });
    })->create();
