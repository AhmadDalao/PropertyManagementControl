<?php

namespace Tests\Feature;

use App\Modules\Dashboard\DashboardPresenter;
use App\Modules\Reports\Presenters\ReportPagePresenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ProductionPayloadCacheTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Cache::flush();
        $this->app->detectEnvironment(fn (): string => 'testing');

        parent::tearDown();
    }

    public function test_dashboard_payload_is_reused_briefly_in_production(): void
    {
        $this->app->detectEnvironment(fn (): string => 'production');
        $actor = $this->createUserWithRole('superadmin');
        $portfolio = $this->createPortfolio();
        $this->createAsset($portfolio);

        $presenter = app(DashboardPresenter::class);
        $initial = $presenter->forUser($actor);
        $this->createAsset($portfolio);
        $cached = $presenter->forUser($actor);

        $this->assertSame($initial['stats']['totalAssets'], $cached['stats']['totalAssets']);

        Cache::flush();
        $refreshed = $presenter->forUser($actor);

        $this->assertSame($initial['stats']['totalAssets'] + 1, $refreshed['stats']['totalAssets']);
    }

    public function test_report_payload_is_scoped_by_actor_and_filters_in_production(): void
    {
        $this->app->detectEnvironment(fn (): string => 'production');
        $actor = $this->createUserWithRole('superadmin');
        $portfolio = $this->createPortfolio();
        $this->createAsset($portfolio, ['parent_id' => null, 'asset_type' => 'building']);
        $filters = [
            'period' => 'year_to_date',
            'date_from' => now()->startOfYear()->toDateString(),
            'date_to' => now()->toDateString(),
            'portfolio_id' => null,
            'property_id' => null,
        ];

        $presenter = app(ReportPagePresenter::class);
        $initial = $presenter->present($actor, $filters);
        $this->createAsset($portfolio, ['parent_id' => null, 'asset_type' => 'building']);
        $cached = $presenter->present($actor, $filters);

        $this->assertCount(count($initial['propertyOptions']), $cached['propertyOptions']);

        Cache::flush();
        $refreshed = $presenter->present($actor, $filters);

        $this->assertCount(count($initial['propertyOptions']) + 1, $refreshed['propertyOptions']);
    }
}
