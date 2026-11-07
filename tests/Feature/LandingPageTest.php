<?php

namespace Tests\Feature;

use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CategorySeeder::class);
    }

    public function test_landing_page_renders_with_localdatehub_identity_and_safety_banner(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('LocalDate');
        $response->assertSee('Hub');
        $response->assertSee('18+ Only');
        $response->assertSee('Men Seeking Women');
        $response->assertSee('Women Seeking Men');
        $response->assertSee('Anti-Triangulation Location Fuzzing');
    }
}
