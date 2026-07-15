<?php

namespace Tests\Feature;

use App\Models\MaintenanceRequest;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminTableSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_asset_table_search_is_scoped_to_their_portfolio(): void
    {
        $ownerPortfolio = $this->createPortfolio(['code' => 'OWN-A', 'slug' => 'own-a']);
        $foreignPortfolio = $this->createPortfolio(['code' => 'OWN-B', 'slug' => 'own-b']);
        $owner = $this->createUserWithRole('owner', $ownerPortfolio);

        $this->createAsset($ownerPortfolio, ['title_en' => 'Palm Tower', 'code' => 'PALM-101']);
        $this->createAsset($foreignPortfolio, ['title_en' => 'Palm Tower Hidden', 'code' => 'PALM-FOREIGN']);

        $this->actingAs($owner)
            ->get(route('assets.index', ['search' => 'Palm', 'per_page' => 10]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/assets/index')
                ->where('assets.total', 1)
                ->where('assets.data.0.code', 'PALM-101')
                ->where('filters.search', 'Palm'));
    }

    public function test_asset_csv_export_uses_the_same_portfolio_scope(): void
    {
        $ownerPortfolio = $this->createPortfolio(['code' => 'EXP-A', 'slug' => 'exp-a']);
        $foreignPortfolio = $this->createPortfolio(['code' => 'EXP-B', 'slug' => 'exp-b']);
        $owner = $this->createUserWithRole('owner', $ownerPortfolio);

        $this->createAsset($ownerPortfolio, ['title_en' => 'Export Visible', 'code' => 'EXPORT-YES']);
        $this->createAsset($foreignPortfolio, ['title_en' => 'Export Hidden', 'code' => 'EXPORT-NO']);

        $response = $this->actingAs($owner)->get(route('exports.resource', ['resource' => 'assets', 'search' => 'Export']));

        $response->assertOk();
        $content = $response->streamedContent();

        $this->assertStringContainsString('EXPORT-YES', $content);
        $this->assertStringNotContainsString('EXPORT-NO', $content);
    }

    public function test_global_search_direct_match_respects_owner_scope(): void
    {
        $ownerPortfolio = $this->createPortfolio(['code' => 'SEA-A', 'slug' => 'sea-a']);
        $foreignPortfolio = $this->createPortfolio(['code' => 'SEA-B', 'slug' => 'sea-b']);
        $owner = $this->createUserWithRole('owner', $ownerPortfolio);

        $asset = $this->createAsset($ownerPortfolio, ['title_en' => 'Search Visible', 'code' => 'SEARCH-YES']);
        $this->createAsset($foreignPortfolio, ['title_en' => 'Search Hidden', 'code' => 'SEARCH-NO']);

        $this->actingAs($owner)
            ->getJson(route('global-search', ['q' => 'SEARCH-YES']))
            ->assertOk()
            ->assertJsonPath('direct_url', route('assets.show', $asset));

        $this->actingAs($owner)
            ->getJson(route('global-search', ['q' => 'SEARCH-NO']))
            ->assertOk()
            ->assertJsonPath('direct_url', '')
            ->assertJsonMissing(['title' => 'Search Hidden']);
    }

    public function test_tenant_global_search_only_returns_tenant_owned_records(): void
    {
        $portfolio = $this->createPortfolio();
        $manager = $this->createUserWithRole('owner', $portfolio);
        $tenantUser = $this->createUserWithRole('tenant', $portfolio, ['name' => 'Tenant Visible']);
        $otherTenantUser = $this->createUserWithRole('tenant', $portfolio, ['name' => 'Tenant Hidden']);
        $tenantProfile = $this->createTenantProfile($portfolio, $tenantUser);
        $otherTenantProfile = $this->createTenantProfile($portfolio, $otherTenantUser);
        $asset = $this->createAsset($portfolio, ['title_en' => 'Visible Unit']);
        $otherAsset = $this->createAsset($portfolio, ['title_en' => 'Hidden Unit']);
        $lease = $this->createLease($portfolio, $tenantProfile, $asset, $manager, ['code' => 'LEASE-VISIBLE']);
        $otherLease = $this->createLease($portfolio, $otherTenantProfile, $otherAsset, $manager, ['code' => 'LEASE-HIDDEN']);

        Payment::query()->create([
            'portfolio_id' => $portfolio->id,
            'lease_id' => $lease->id,
            'tenant_profile_id' => $tenantProfile->id,
            'recorded_by_user_id' => $manager->id,
            'reference' => 'PAY-VISIBLE',
            'type' => 'rent',
            'method' => 'cash',
            'status' => 'posted',
            'received_on' => now()->toDateString(),
            'amount' => 100,
            'currency' => 'SAR',
        ]);

        Payment::query()->create([
            'portfolio_id' => $portfolio->id,
            'lease_id' => $otherLease->id,
            'tenant_profile_id' => $otherTenantProfile->id,
            'recorded_by_user_id' => $manager->id,
            'reference' => 'PAY-HIDDEN',
            'type' => 'rent',
            'method' => 'cash',
            'status' => 'posted',
            'received_on' => now()->toDateString(),
            'amount' => 100,
            'currency' => 'SAR',
        ]);

        MaintenanceRequest::query()->create([
            'portfolio_id' => $portfolio->id,
            'asset_id' => $asset->id,
            'lease_id' => $lease->id,
            'tenant_profile_id' => $tenantProfile->id,
            'submitted_by_user_id' => $tenantUser->id,
            'category' => 'electricity',
            'priority' => 'medium',
            'status' => 'open',
            'title' => 'Visible breaker issue',
            'description' => 'Breaker is down.',
            'requested_at' => now(),
        ]);

        $this->actingAs($tenantUser)
            ->getJson(route('global-search', ['q' => 'VISIBLE']))
            ->assertOk()
            ->assertJsonFragment(['title' => 'LEASE-VISIBLE'])
            ->assertJsonFragment(['title' => 'PAY-VISIBLE'])
            ->assertJsonMissing(['title' => 'LEASE-HIDDEN'])
            ->assertJsonMissing(['title' => 'PAY-HIDDEN']);
    }
}
