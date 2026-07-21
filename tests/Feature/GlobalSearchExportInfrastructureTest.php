<?php

namespace Tests\Feature;

use App\Models\AssetStakeholder;
use App\Models\ExpenseEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class GlobalSearchExportInfrastructureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_global_search_normalizes_short_queries_and_rejects_invalid_input(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);

        $this->actingAs($owner)
            ->getJson(route('global-search', ['q' => ' x ']))
            ->assertOk()
            ->assertJsonPath('query', 'x')
            ->assertJsonPath('message', trans('app.search.minimum'))
            ->assertJsonCount(0, 'results');

        $this->actingAs($owner)
            ->getJson(route('global-search', ['q' => ['invalid']]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('q');

        $this->actingAs($owner)
            ->getJson(route('global-search', ['q' => str_repeat('x', 121)]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('q');
    }

    public function test_expense_search_is_localized_and_portfolio_scoped(): void
    {
        $portfolio = $this->createPortfolio();
        $foreignPortfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);

        ExpenseEntry::query()->create([
            'portfolio_id' => $portfolio->id,
            'created_by_user_id' => $owner->id,
            'category' => 'maintenance',
            'title' => 'Scoped roof reserve',
            'incurred_on' => now()->toDateString(),
            'amount' => 800,
            'currency' => 'SAR',
            'vendor_name' => 'Scoped expense vendor',
            'status' => 'posted',
        ]);
        ExpenseEntry::query()->create([
            'portfolio_id' => $foreignPortfolio->id,
            'category' => 'maintenance',
            'title' => 'Foreign roof reserve',
            'incurred_on' => now()->toDateString(),
            'amount' => 900,
            'currency' => 'SAR',
            'vendor_name' => 'Scoped expense vendor',
            'status' => 'posted',
        ]);

        $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->getJson(route('global-search', ['q' => 'Scoped expense vendor']))
            ->assertOk()
            ->assertJsonFragment([
                'group' => trans('app.nav.expenses', [], 'ar'),
                'title' => 'Scoped roof reserve',
            ])
            ->assertJsonMissing(['title' => 'Foreign roof reserve']);
    }

    public function test_tenants_cannot_use_any_administration_export(): void
    {
        $portfolio = $this->createPortfolio();
        $tenant = $this->createUserWithRole('tenant', $portfolio);

        foreach ([
            'assets',
            'tenants',
            'leases',
            'payments',
            'maintenance-requests',
            'expenses',
            'documents',
            'users',
            'portfolios',
            'cms-pages',
            'media-files',
        ] as $resource) {
            $this->actingAs($tenant)
                ->get(route('exports.resource', ['resource' => $resource]))
                ->assertForbidden();
        }
    }

    public function test_unknown_export_resource_returns_not_found(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);

        $this->actingAs($owner)
            ->get(route('exports.resource', ['resource' => 'not-a-resource']))
            ->assertNotFound();
    }

    public function test_asset_index_and_export_share_relational_search_filters(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $manager = $this->createUserWithRole('property_manager', $portfolio, [
            'email' => 'search-owner@example.test',
        ]);
        $matchingAsset = $this->createAsset($portfolio, [
            'title_en' => 'Search filter match',
            'code' => 'FILTER-MATCH',
        ]);
        $this->createAsset($portfolio, [
            'title_en' => 'Search filter decoy',
            'code' => 'FILTER-DECOY',
        ]);

        AssetStakeholder::query()->create([
            'portfolio_id' => $portfolio->id,
            'asset_id' => $matchingAsset->id,
            'user_id' => $manager->id,
            'relationship_type' => 'manager',
            'is_primary' => true,
        ]);

        $this->actingAs($owner)
            ->get(route('assets.index', ['search' => 'search-owner@example.test']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('assets.total', 1)
                ->where('assets.data.0.code', 'FILTER-MATCH'));

        $export = $this->actingAs($owner)
            ->get(route('exports.resource', [
                'resource' => 'assets',
                'search' => 'search-owner@example.test',
            ]))
            ->assertOk();
        $worksheet = $this->xlsxWorksheetXml($export);

        $this->assertStringContainsString('FILTER-MATCH', $worksheet);
        $this->assertStringNotContainsString('FILTER-DECOY', $worksheet);
    }
}
