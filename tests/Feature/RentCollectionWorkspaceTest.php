<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Lease;
use App\Models\LeaseInstallment;
use App\Models\Portfolio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RentCollectionWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_owner_sees_a_scoped_installment_collection_desk_with_property_context(): void
    {
        $portfolio = $this->createPortfolio();
        $foreignPortfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $foreignOwner = $this->createUserWithRole('owner', $foreignPortfolio);
        [$lease, $root] = $this->leaseInsideProperty($portfolio, $owner, 'Collection Tenant', 'Owner Tower');
        [$foreignLease] = $this->leaseInsideProperty($foreignPortfolio, $foreignOwner, 'Foreign Tenant', 'Foreign Tower');

        $overdue = $this->installment($lease, [
            'label' => 'Owner overdue rent',
            'due_date' => today()->subDays(5),
            'amount_due' => 1000,
            'amount_paid' => 200,
            'status' => 'overdue',
        ]);
        $this->installment($lease, [
            'sequence' => 2,
            'label' => 'Owner upcoming rent',
            'due_date' => today()->addDays(10),
            'amount_due' => 700,
        ]);
        $this->installment($lease, [
            'sequence' => 3,
            'label' => 'Owner settled rent',
            'due_date' => today()->subMonth(),
            'amount_due' => 500,
            'amount_paid' => 500,
            'status' => 'paid',
        ]);
        $this->installment($foreignLease, [
            'label' => 'Foreign overdue rent',
            'due_date' => today()->subDays(20),
            'amount_due' => 9000,
            'status' => 'overdue',
        ]);

        $this->actingAs($owner)
            ->get(route('rent-collection.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/rent-collection/index')
                ->where('filters.status', 'actionable')
                ->where('installments.total', 2)
                ->where('installments.data.0.id', $overdue->id)
                ->where('installments.data.0.tenant.name', 'Collection Tenant')
                ->where('installments.data.0.property.id', $root->id)
                ->where('installments.data.0.property.title_en', 'Owner Tower')
                ->where('installments.data.0.status', 'overdue')
                ->where('installments.data.0.outstanding_amount', fn (int|float $value) => (float) $value === 800.0)
                ->where('collectionInsights.open_count', 2)
                ->where('collectionInsights.overdue_count', 1)
                ->where('collectionInsights.outstanding_amount', fn (int|float $value) => (float) $value === 1500.0)
                ->where('collectionInsights.overdue_amount', fn (int|float $value) => (float) $value === 800.0)
                ->where('collectionInsights.due_next_30_amount', fn (int|float $value) => (float) $value === 700.0)
                ->where('counts', fn ($counts): bool => collect($counts)->firstWhere('filter.status', 'paid')['value'] === 1)
                ->where('propertyOptions.0.id', $root->id));
    }

    public function test_property_filters_search_pagination_and_xlsx_export_share_the_same_scope(): void
    {
        $portfolio = $this->createPortfolio();
        $foreignPortfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $foreignOwner = $this->createUserWithRole('owner', $foreignPortfolio);
        [$firstLease, $firstRoot] = $this->leaseInsideProperty($portfolio, $owner, 'North Tenant', 'North Tower');
        [$secondLease] = $this->leaseInsideProperty($portfolio, $owner, 'South Tenant', 'South Tower');
        [, $foreignRoot] = $this->leaseInsideProperty($foreignPortfolio, $foreignOwner, 'Foreign Tenant', 'Foreign Tower');

        foreach (range(1, 12) as $sequence) {
            $this->installment($firstLease, [
                'sequence' => $sequence,
                'label' => "North collection {$sequence}",
                'due_date' => today()->addDays($sequence),
                'amount_due' => 100 + $sequence,
            ]);
        }
        $this->installment($secondLease, [
            'label' => 'South collection only',
            'due_date' => today()->addDay(),
            'amount_due' => 900,
        ]);

        $this->actingAs($owner)
            ->get(route('rent-collection.index', [
                'property_id' => $firstRoot->id,
                'status' => 'all',
                'search' => 'North collection',
                'per_page' => 10,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('installments.total', 12)
                ->has('installments.data', 10)
                ->where('filters.property_id', (string) $firstRoot->id)
                ->where('filters.search', 'North collection')
                ->where('installments.data.0.property.id', $firstRoot->id));

        $export = $this->actingAs($owner)
            ->get(route('exports.resource', [
                'resource' => 'rent-collection',
                'property_id' => $firstRoot->id,
                'status' => 'all',
                'search' => 'North collection',
            ]))
            ->assertOk();
        $worksheet = $this->xlsxWorksheetXml($export);

        $this->assertStringContainsString('North collection 1', $worksheet);
        $this->assertStringNotContainsString('South collection only', $worksheet);

        $this->actingAs($owner)
            ->get(route('rent-collection.index', ['property_id' => $foreignRoot->id]))
            ->assertForbidden();
    }

    public function test_arabic_collection_copy_and_generated_installment_labels_are_localized(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        [$lease] = $this->leaseInsideProperty($portfolio, $owner, 'Arabic Tenant', 'Arabic Tower');
        $installment = $this->installment($lease, [
            'label' => 'Rent Jul 2026',
            'period_start' => today()->startOfMonth(),
            'period_end' => today()->endOfMonth(),
            'due_date' => today(),
        ]);

        $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('rent-collection.index', ['status' => 'all']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('app.locale', 'ar')
                ->where('app.direction', 'rtl')
                ->where('app.translations.nav.rent_collection', 'تحصيل الإيجارات')
                ->where('app.translations.rent_collection.title', 'تحصيل الإيجارات')
                ->where('installments.data.0.id', $installment->id)
                ->where('installments.data.0.label', fn (string $label) => str_starts_with($label, 'إيجار ')
                    && preg_match('/[٠-٩]/u', $label) === 1
                    && ! str_contains($label, '/')));
    }

    public function test_tenants_and_disabled_portfolios_cannot_open_or_export_collection_data(): void
    {
        $portfolio = $this->createPortfolio();
        $tenant = $this->createUserWithRole('tenant', $portfolio);

        $this->actingAs($tenant)
            ->get(route('rent-collection.index'))
            ->assertForbidden();
        $this->actingAs($tenant)
            ->get(route('exports.resource', ['resource' => 'rent-collection']))
            ->assertForbidden();

        $disabledPortfolio = $this->createPortfolio([
            'module_settings' => ['payments' => false],
        ]);
        $owner = $this->createUserWithRole('owner', $disabledPortfolio);

        $this->actingAs($owner)
            ->get(route('rent-collection.index'))
            ->assertForbidden();
        $this->actingAs($owner)
            ->get(route('exports.resource', ['resource' => 'rent-collection']))
            ->assertForbidden();
    }

    /** @return array{Lease, Asset} */
    private function leaseInsideProperty(
        Portfolio $portfolio,
        User $owner,
        string $tenantName,
        string $propertyName,
    ): array {
        $root = $this->createAsset($portfolio, [
            'asset_type' => 'building',
            'title_en' => $propertyName,
            'title_ar' => "عقار {$propertyName}",
            'rentable' => false,
        ]);
        $unit = $this->createAsset($portfolio, [
            'parent_id' => $root->id,
            'title_en' => "{$propertyName} Unit",
            'title_ar' => "وحدة {$propertyName}",
        ]);
        $tenant = $this->createTenantProfile(
            $portfolio,
            $this->createUserWithRole('tenant', $portfolio, ['name' => $tenantName]),
        );

        return [
            $this->createLease(
                $portfolio,
                $tenant,
                $unit,
                $owner,
                ['code' => str($propertyName)->slug()->upper()->append('-LEASE')->toString()],
                syncInstallments: false,
            ),
            $root,
        ];
    }

    /** @param array<string, mixed> $attributes */
    private function installment(Lease $lease, array $attributes = []): LeaseInstallment
    {
        return LeaseInstallment::query()->create(array_merge([
            'lease_id' => $lease->id,
            'sequence' => 1,
            'line_type' => 'rent',
            'label' => 'Rent installment',
            'period_start' => today()->startOfMonth(),
            'period_end' => today()->endOfMonth(),
            'due_date' => today()->addWeek(),
            'amount_due' => 1000,
            'amount_paid' => 0,
            'status' => 'pending',
        ], $attributes));
    }
}
