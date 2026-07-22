<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetStakeholder;
use App\Models\ExpenseEntry;
use App\Models\MaintenanceRequest;
use App\Modules\Assets\Actions\ManageAssets;
use App\Modules\Assets\Presenters\AssetFormPresenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AssetModuleSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_asset_edit_cannot_bypass_the_archive_workflow(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $asset = $this->createAsset($portfolio);
        $payload = $this->payload($asset, ['status' => 'archived']);

        $this->actingAs($owner)
            ->put(route('assets.update', $asset), $payload)
            ->assertSessionHasErrors('status');

        $this->assertSame('active', $asset->fresh()->status);

        try {
            app(ManageAssets::class)->update($owner, $asset, $payload);
            $this->fail('Direct action reuse bypassed the archive workflow.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('status', $exception->errors());
        }

        $this->assertSame('active', $asset->fresh()->status);
    }

    public function test_stakeholder_changes_preserve_history_and_enforce_role_rules(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio, ['name' => 'Original Owner']);
        $replacement = $this->createUserWithRole('owner', $portfolio, ['name' => 'Replacement Owner']);
        $manager = $this->createUserWithRole('property_manager', $portfolio);
        $tenant = $this->createUserWithRole('tenant', $portfolio);
        $assets = app(ManageAssets::class);
        $asset = $assets->create($owner, $this->payload(null, [
            'title_en' => 'History Building',
            'title_ar' => 'مبنى السجل',
            'primary_owner_user_id' => $owner->id,
            'primary_manager_user_id' => $manager->id,
        ]));

        $assets->update($owner, $asset, $this->payload($asset, [
            'primary_owner_user_id' => $replacement->id,
            'primary_manager_user_id' => $manager->id,
        ]));

        $this->assertDatabaseHas('asset_stakeholders', [
            'asset_id' => $asset->id,
            'user_id' => $replacement->id,
            'relationship_type' => 'owner',
            'ends_on' => null,
        ]);
        $historicalOwner = AssetStakeholder::query()
            ->where('asset_id', $asset->id)
            ->where('user_id', $owner->id)
            ->where('relationship_type', 'owner')
            ->firstOrFail();
        $this->assertNotNull($historicalOwner->ends_on);
        $this->assertSame(3, $asset->stakeholders()->count());
        $this->assertSame(2, $asset->currentStakeholders()->count());

        try {
            $assets->update($owner, $asset, $this->payload($asset, [
                'primary_owner_user_id' => $tenant->id,
                'primary_manager_user_id' => $manager->id,
            ]));
            $this->fail('A tenant was accepted as an asset owner.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('primary_owner_user_id', $exception->errors());
        }
    }

    public function test_asset_form_options_follow_the_selected_portfolio_and_assignment_roles(): void
    {
        $portfolio = $this->createPortfolio();
        $foreignPortfolio = $this->createPortfolio();
        $superadmin = $this->createUserWithRole('superadmin');
        $owner = $this->createUserWithRole('owner', $portfolio);
        $manager = $this->createUserWithRole('property_manager', $portfolio);
        $tenant = $this->createUserWithRole('tenant', $portfolio);
        $foreignOwner = $this->createUserWithRole('owner', $foreignPortfolio);
        $parent = $this->createAsset($portfolio, ['asset_type' => 'building']);
        $child = $this->createAsset($portfolio, ['parent_id' => $parent->id]);
        $foreignAsset = $this->createAsset($foreignPortfolio);
        $presenter = app(AssetFormPresenter::class);
        $form = $presenter->present($superadmin, defaults: ['portfolio_id' => $portfolio->id]);

        $parentValues = $this->fieldValues($form, 'parent_id');
        $ownerValues = $this->fieldValues($form, 'primary_owner_user_id');
        $managerValues = $this->fieldValues($form, 'primary_manager_user_id');
        $this->assertContains((string) $parent->id, $parentValues);
        $this->assertContains((string) $child->id, $parentValues);
        $this->assertNotContains((string) $foreignAsset->id, $parentValues);
        $this->assertContains((string) $owner->id, $ownerValues);
        $this->assertNotContains((string) $manager->id, $ownerValues);
        $this->assertNotContains((string) $tenant->id, $ownerValues);
        $this->assertNotContains((string) $foreignOwner->id, $ownerValues);
        $this->assertContains((string) $owner->id, $managerValues);
        $this->assertContains((string) $manager->id, $managerValues);
        $this->assertNotContains((string) $tenant->id, $managerValues);

        $editForm = $presenter->present($superadmin, $parent);
        $editParentValues = $this->fieldValues($editForm, 'parent_id');
        $this->assertNotContains((string) $parent->id, $editParentValues);
        $this->assertNotContains((string) $child->id, $editParentValues);
    }

    public function test_historical_stakeholders_do_not_pollute_current_asset_search(): void
    {
        $portfolio = $this->createPortfolio();
        $actor = $this->createUserWithRole('owner', $portfolio);
        $historical = $this->createUserWithRole('owner', $portfolio, ['name' => 'Historical Search Owner']);
        $current = $this->createUserWithRole('owner', $portfolio, ['name' => 'Current Search Owner']);
        $asset = $this->createAsset($portfolio);
        $asset->stakeholders()->createMany([
            [
                'portfolio_id' => $portfolio->id,
                'user_id' => $historical->id,
                'relationship_type' => 'owner',
                'is_primary' => true,
                'starts_on' => now()->subYear()->toDateString(),
                'ends_on' => now()->subDay()->toDateString(),
            ],
            [
                'portfolio_id' => $portfolio->id,
                'user_id' => $current->id,
                'relationship_type' => 'owner',
                'is_primary' => true,
                'starts_on' => now()->toDateString(),
            ],
        ]);

        $this->actingAs($actor)
            ->get(route('assets.index', ['search' => 'Historical Search Owner']))
            ->assertInertia(fn (Assert $page) => $page->where('assets.total', 0));
        $this->actingAs($actor)
            ->get(route('assets.index', ['search' => 'Current Search Owner']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('assets.total', 1)
                ->where('assets.data.0.stakeholders.0.user.name', 'Current Search Owner'));
    }

    public function test_asset_detail_limits_related_payloads_but_keeps_exact_totals(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenantUser = $this->createUserWithRole('tenant', $portfolio);
        $tenant = $this->createTenantProfile($portfolio, $tenantUser);
        $asset = $this->createAsset($portfolio, ['asset_type' => 'building']);

        foreach (range(1, 10) as $index) {
            $this->createAsset($portfolio, [
                'parent_id' => $asset->id,
                'code' => sprintf('BOUND-CHILD-%02d', $index),
            ]);
            $this->createLease($portfolio, $tenant, $asset, $owner, [
                'code' => sprintf('BOUND-LEASE-%02d', $index),
                'status' => 'draft',
            ], false);
            MaintenanceRequest::query()->create([
                'portfolio_id' => $portfolio->id,
                'asset_id' => $asset->id,
                'tenant_profile_id' => $tenant->id,
                'submitted_by_user_id' => $tenantUser->id,
                'category' => 'general',
                'priority' => 'low',
                'status' => 'open',
                'title' => "Bounded request {$index}",
                'description' => 'Payload limit check.',
                'requested_at' => now()->subMinutes($index),
            ]);
            ExpenseEntry::query()->create([
                'portfolio_id' => $portfolio->id,
                'asset_id' => $asset->id,
                'created_by_user_id' => $owner->id,
                'title' => "Bounded expense {$index}",
                'category' => 'maintenance',
                'status' => 'posted',
                'amount' => 10,
                'currency' => 'SAR',
                'incurred_on' => today()->subDays($index),
            ]);
        }

        $this->actingAs($owner)
            ->get(route('assets.show', $asset))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('detailPage.stats.2.value', 10)
                ->where('detailPage.stats.3.value', 10)
                ->where('detailPage.stats.4.value', 10)
                ->has('detailPage.related.0.rows', 8)
                ->has('detailPage.related.1.rows', 8)
                ->has('detailPage.related.2.rows', 8)
                ->has('detailPage.related.3.rows', 8));
    }

    public function test_arabic_asset_detail_uses_arabic_interface_copy(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio, ['preferred_locale' => 'ar']);
        $asset = $this->createAsset($portfolio, [
            'title_en' => 'Arabic Detail Asset',
            'title_ar' => 'أصل التفاصيل العربية',
        ]);

        $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('assets.show', $asset))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('app.locale', 'ar')
                ->where('app.direction', 'rtl')
                ->where('detailPage.header.eyebrow', 'تفاصيل الأصل')
                ->where('detailPage.header.title', 'أصل التفاصيل العربية')
                ->where('detailPage.decisionCards.0.title', 'جاهزية الخريطة')
                ->where('detailPage.related.0.title', 'الأصول الفرعية'));
    }

    /** @param array<string, mixed> $overrides @return array<string, mixed> */
    private function payload(?Asset $asset = null, array $overrides = []): array
    {
        return array_merge([
            'asset_type' => $asset?->asset_type ?? 'building',
            'usage_type' => $asset?->usage_type ?? 'residential',
            'title_en' => $asset?->title_en ?? 'Managed asset',
            'title_ar' => $asset?->title_ar ?? 'أصل مدار',
            'status' => $asset?->status ?? 'active',
            'occupancy_status' => $asset?->occupancy_status ?? 'vacant',
            'rentable' => $asset?->rentable ?? false,
            'valuation_amount' => $asset?->valuation_amount ?? 0,
            'currency' => $asset?->currency ?? 'SAR',
            'area' => $asset?->area ?? 0,
        ], $overrides);
    }

    /** @param array<string, mixed> $form @return array<int, string> */
    private function fieldValues(array $form, string $name): array
    {
        $field = collect($form['fields'])->firstWhere('name', $name);

        return collect($field['options'] ?? [])->pluck('value')->map(strval(...))->all();
    }
}
