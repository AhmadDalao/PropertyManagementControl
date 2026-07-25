<?php

namespace Tests\Feature;

use App\Models\Asset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PropertyContextWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_owner_property_scope_persists_across_operational_pages_and_can_be_cleared(): void
    {
        $portfolio = $this->createPortfolio(['code' => 'OWNER-PORT']);
        $owner = $this->createUserWithRole('owner', $portfolio);
        $first = $this->root($portfolio->id, 'FIRST', 'First Tower', 'البرج الأول');
        $second = $this->root($portfolio->id, 'SECOND', 'Second Tower', 'البرج الثاني');
        $this->createAsset($portfolio, [
            'parent_id' => $first->id,
            'code' => 'FIRST-U101',
            'title_en' => 'Unit 101',
        ]);
        $inactive = $this->root($portfolio->id, 'INACTIVE', 'Inactive Tower', 'برج غير نشط');
        $inactive->update(['status' => 'inactive']);

        $this->actingAs($owner)
            ->get(route('assets.index', ['property_id' => $first->id]))
            ->assertOk()
            ->assertSessionHas('property_context_id', $first->id)
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.property_id', (string) $first->id)
                ->where('propertyContext.selected.id', $first->id)
                ->where('propertyContext.options', fn (mixed $options): bool => collect($options)
                    ->pluck('id')
                    ->all() === [$first->id, $second->id]));

        $this->actingAs($owner)
            ->get(route('leases.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.property_id', (string) $first->id)
                ->where('propertyContext.selected.id', $first->id));

        $export = $this->actingAs($owner)->get(route('exports.resource', [
            'resource' => 'assets',
        ]));
        $export->assertOk();
        $worksheet = $this->xlsxWorksheetXml($export);
        $this->assertStringContainsString('FIRST', $worksheet);
        $this->assertStringNotContainsString('SECOND', $worksheet);

        $this->actingAs($owner)
            ->get(route('assets.index', ['property_id' => 'all']))
            ->assertOk()
            ->assertSessionMissing('property_context_id')
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.property_id', 'all')
                ->where('propertyContext.selected', null));
    }

    public function test_manager_sees_only_assigned_roots_and_tenant_receives_no_property_selector(): void
    {
        $portfolio = $this->createPortfolio();
        $manager = $this->createUserWithRole('property_manager', $portfolio);
        $tenant = $this->createUserWithRole('tenant', $portfolio);
        $assigned = $this->root($portfolio->id, 'ASSIGNED', 'Assigned Tower', 'البرج المسند');
        $hidden = $this->root($portfolio->id, 'HIDDEN', 'Hidden Tower', 'البرج المخفي');
        $this->assignManagerToAsset($manager, $assigned);

        $this->actingAs($manager)
            ->get(route('dashboard', ['property_id' => $assigned->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('propertyContext.assignment_restricted', true)
                ->where('propertyContext.selected.id', $assigned->id)
                ->where('propertyContext.options', fn (mixed $options): bool => collect($options)
                    ->pluck('id')
                    ->all() === [$assigned->id])
                ->where('propertyFocus.property_count', 1));

        $this->actingAs($tenant)
            ->get(route('dashboard', ['property_id' => $assigned->id]))
            ->assertOk()
            ->assertSessionMissing('property_context_id')
            ->assertInertia(fn (Assert $page) => $page
                ->where('mode', 'tenant')
                ->where('propertyContext', null));
    }

    public function test_stale_or_foreign_session_scope_is_removed_before_a_directory_query(): void
    {
        $portfolio = $this->createPortfolio();
        $foreignPortfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $local = $this->root($portfolio->id, 'LOCAL', 'Local Tower', 'البرج المحلي');
        $foreign = $this->root($foreignPortfolio->id, 'FOREIGN', 'Foreign Tower', 'البرج الخارجي');

        $this->actingAs($owner)
            ->withSession(['property_context_id' => $foreign->id])
            ->get(route('assets.index'))
            ->assertOk()
            ->assertSessionMissing('property_context_id')
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.property_id', 'all')
                ->where('propertyContext.selected', null)
                ->where('propertyContext.options.0.id', $local->id));
    }

    public function test_property_map_uses_the_remembered_root_and_keeps_its_descendants(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $first = $this->root($portfolio->id, 'MAP-A', 'Mapped Tower', 'البرج المحدد');
        $second = $this->root($portfolio->id, 'MAP-B', 'Other Tower', 'البرج الآخر');
        $this->createAsset($portfolio, [
            'parent_id' => $first->id,
            'code' => 'MAP-A-U101',
            'title_en' => 'Mapped Unit',
            'rentable' => true,
        ]);

        $this->actingAs($owner)
            ->get(route('property-map.index', ['property_id' => $first->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.property_id', $first->id)
                ->where('propertyContext.selected.id', $first->id)
                ->where('propertyMap.assets', fn (mixed $assets): bool => collect($assets)
                    ->pluck('id')
                    ->contains($first->id)
                    && ! collect($assets)->pluck('id')->contains($second->id))
                ->where('propertyMap.assets.0.children_count', 1));
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
