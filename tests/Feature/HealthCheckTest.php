<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    public function test_liveness_endpoint_returns_successful_status(): void
    {
        $response = $this->getJson(route('health.liveness'));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'status' => 200,
                'message' => 'LocalDateHub service is online',
                'data' => [
                    'status' => 'healthy',
                ],
            ]);
    }

    public function test_detailed_health_endpoint_is_forbidden_without_secret_key(): void
    {
        $response = $this->getJson(route('health.readiness'));

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'status' => 403,
                'error_code' => 'PERMISSION_DENIED',
            ]);
    }

    public function test_detailed_health_endpoint_succeeds_with_valid_secret_key(): void
    {
        $secret = config('localdatehub.health_check.secret_key');

        $response = $this->withHeaders([
            'X-Health-Key' => $secret,
        ])->getJson(route('health.readiness'));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'status' => 200,
                'data' => [
                    'status' => 'healthy',
                ],
            ]);
    }
}
