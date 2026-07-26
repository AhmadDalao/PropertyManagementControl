<?php

namespace Tests\Feature;

use App\Models\Asset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PortfolioControlWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_owner_can_rank_search_filter_and_page_the_complete_portfolio(): void
    {
        $portfolio = $this->createPortfolio([
            'code' => 'OWNER-PORT',
            'name_en' => 'Owner Portfolio',
            'name_ar' => 'محفظة المالك',
        ]);
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenantUser = $this->createUserWithRole('tenant', $portfolio);
        $tenant = $this->createTenantProfile($portfolio, $tenantUser);
        $risk = $this->root(
            $portfolio->id,
            'RISK-TOWER',
            'Risk Tower',
            'برج المخاطر',
        );
        $unit = $this->createAsset($portfolio, [
            'parent_id' => $risk->id,
            'code' => 'RISK-U101',
            'occupancy_status' => 'occupied',
        ]);
        $this->createLease($portfolio, $tenant, $unit, $owner);

        foreach (range(1, 13) as $index) {
            $this->root(
                $portfolio->id,
                "TOWER-{$index}",
                "Tower {$index}",
                "برج {$index}",
            );
        }

        $foreignPortfolio = $this->createPortfolio();
        $foreign = $this->root(
            $foreignPortfolio->id,
            'FOREIGN',
            'Foreign Tower',
            'البرج الخارجي',
        );

        $this->actingAs($owner)
            ->get(route('portfolio-control.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/portfolio-control/index')
                ->where(
                    'app.translations.portfolio_control.title',
                    'Portfolio control',
                )
                ->where('propertyContext', null)
                ->where('summary.properties', 14)
                ->where('summary.risk', 1)
                ->where('properties.total', 14)
                ->where('properties.per_page', 12)
                ->has('properties.data', 12)
                ->where('properties.data.0.id', $risk->id)
                ->where('properties.data', fn (mixed $rows): bool => ! collect($rows)
                    ->pluck('id')
                    ->contains($foreign->id))
                ->where('portfolioOptions.0.id', $portfolio->id));

        $this->actingAs($owner)
            ->get(route('portfolio-control.index', [
                'search' => 'Risk',
                'attention' => 'risk',
                'sort' => 'arrears',
                'per_page' => 24,
                'locale' => 'ar',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('app.locale', 'ar')
                ->where(
                    'app.translations.portfolio_control.title',
                    'تحكم المحفظة',
                )
                ->where('filters.search', 'Risk')
                ->where('filters.attention', 'risk')
                ->where('filters.sort', 'arrears')
                ->where('properties.total', 1)
                ->where('properties.data.0.id', $risk->id)
                ->where('properties.data.0.title_ar', 'برج المخاطر'));
    }

    public function test_manager_only_sees_assigned_properties_and_tenant_is_forbidden(): void
    {
        $portfolio = $this->createPortfolio();
        $manager = $this->createUserWithRole('property_manager', $portfolio);
        $tenant = $this->createUserWithRole('tenant', $portfolio);
        $assigned = $this->root(
            $portfolio->id,
            'ASSIGNED',
            'Assigned Tower',
            'البرج المسند',
        );
        $hidden = $this->root(
            $portfolio->id,
            'HIDDEN',
            'Hidden Tower',
            'البرج المخفي',
        );
        $this->assignManagerToAsset($manager, $assigned);

        $this->actingAs($manager)
            ->get(route('portfolio-control.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('summary.properties', 1)
                ->where('properties.total', 1)
                ->where('properties.data.0.id', $assigned->id)
                ->where('properties.data', fn (mixed $rows): bool => ! collect($rows)
                    ->pluck('id')
                    ->contains($hidden->id)));

        $this->actingAs($tenant)
            ->get(route('portfolio-control.index'))
            ->assertForbidden();
    }

    public function test_foreign_portfolio_filter_cannot_leak_owner_data(): void
    {
        $portfolio = $this->createPortfolio();
        $foreignPortfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $local = $this->root(
            $portfolio->id,
            'LOCAL',
            'Local Tower',
            'البرج المحلي',
        );
        $foreign = $this->root(
            $foreignPortfolio->id,
            'FOREIGN',
            'Foreign Tower',
            'البرج الخارجي',
        );

        $this->actingAs($owner)
            ->get(route('portfolio-control.index', [
                'portfolio_id' => $foreignPortfolio->id,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('summary.properties', 0)
                ->where('properties.total', 0)
                ->where('portfolioOptions.0.id', $portfolio->id)
                ->where('portfolioOptions', fn (mixed $options): bool => ! collect($options)
                    ->pluck('id')
                    ->contains($foreignPortfolio->id))
                ->where('properties.data', fn (mixed $rows): bool => ! collect($rows)
                    ->pluck('id')
                    ->contains($local->id)
                    && ! collect($rows)->pluck('id')->contains($foreign->id)));
    }

    private function root(
        int $portfolioId,
        string $code,
        string $titleEn,
        string $titleAr,
    ): Asset {
        return Asset::query()->create([
            'portfolio_id' => $portfolioId,
            'parent_id' => null,
            'asset_type' => 'building',
            'usage_type' => 'residential',
            'title_en' => $titleEn,
            'title_ar' => $titleAr,
            'code' => $code,
            'slug' => strtolower($code),
            'status' => 'active',
            'occupancy_status' => 'vacant',
            'rentable' => false,
            'valuation_amount' => 0,
            'currency' => 'SAR',
        ]);
    }
}
