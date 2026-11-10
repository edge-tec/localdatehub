<?php

namespace Tests\Feature;

use App\Livewire\Admin\AdminEnterpriseHub;
use App\Models\PlatformTrafficLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminAnalyticsReportTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'account_status' => 'active',
            'is_age_verified' => true,
        ]);
    }

    private const BROWSER_UA = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0 Safari/537.36';

    public function test_page_reloads_by_same_visitor_are_counted_once(): void
    {
        PlatformTrafficLog::query()->delete();

        for ($i = 0; $i < 4; $i++) {
            $this->withHeaders(['User-Agent' => self::BROWSER_UA])->get('/');
        }

        $this->assertSame(1, PlatformTrafficLog::where('is_bot', false)->count());
    }

    public function test_admin_dashboard_reloads_are_not_tracked(): void
    {
        PlatformTrafficLog::query()->delete();

        $this->actingAs($this->admin);
        for ($i = 0; $i < 3; $i++) {
            $this->withHeaders(['User-Agent' => self::BROWSER_UA])->get('/admin/dashboard');
            $this->withHeaders(['User-Agent' => self::BROWSER_UA])->get('/');
        }

        $this->assertSame(0, PlatformTrafficLog::count());
    }

    public function test_dashboard_unique_and_gross_toggle_and_analytics_filters_render(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(AdminEnterpriseHub::class, ['initialTab' => 'dashboard'])
            ->call('setDashboardViewMode', 'gross')
            ->assertSet('dashboardViewMode', 'gross')
            ->call('setDashboardChartType', 'chart')
            ->assertSet('dashboardChartType', 'chart')
            ->assertSee('Gross Views');

        $component = Livewire::test(AdminEnterpriseHub::class, ['initialTab' => 'analytics'])
            ->assertSee('Analytics &amp; Reports', false)
            ->assertSee('Country / Geo Report');

        foreach (['today', '7d', '30d', '90d', 'all'] as $period) {
            $component->set('analyticsPeriod', $period)->assertOk();
        }
        foreach (['visitors', 'posts', 'countries', 'devices', 'age', 'all'] as $dim) {
            $component->call('setAnalyticsDimension', $dim)->assertSet('analyticsDimension', $dim);
        }
        foreach (['graph', 'chart', 'both'] as $mode) {
            $component->call('setAnalyticsVisualMode', $mode)->assertOk();
        }

        $component->set('analyticsCountryFilter', 'US')
            ->set('analyticsDeviceFilter', 'Mobile')
            ->set('analyticsAgeFilter', '25-34')
            ->call('setAnalyticsViewMode', 'unique')
            ->assertOk()
            ->assertSee('Age Demographics')
            ->call('resetAnalyticsFilters')
            ->assertSet('analyticsAgeFilter', 'all')
            ->assertSet('analyticsCountryFilter', 'all');
    }
}
