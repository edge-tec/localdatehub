<?php

namespace App\Http\Controllers;

use App\Support\Health\HealthCheckService;
use App\Support\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

class HealthController extends Controller
{
    public function __construct(
        protected HealthCheckService $healthCheckService
    ) {}

    /**
     * Basic liveness probe (public).
     */
    public function liveness(): JsonResponse
    {
        $status = $this->healthCheckService->checkLiveness();
        return ApiResponse::success($status, 'LocalDateHub service is online');
    }

    /**
     * Comprehensive readiness probe (protected by HealthCheckAuthMiddleware).
     */
    public function readiness(): JsonResponse
    {
        $status = $this->healthCheckService->checkReadiness();
        $isHealthy = $status['status'] === 'healthy';

        return ApiResponse::success(
            data: $status,
            message: $isHealthy ? 'All system dependencies are healthy' : 'System operating in degraded mode',
            statusCode: $isHealthy ? 200 : 503
        );
    }
}
