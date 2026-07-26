<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Lease;
use App\Models\LeaseInstallment;
use App\Models\LeaseMoveOut;
use App\Models\MaintenanceRequest;
use App\Models\MaintenanceVendor;
use App\Models\MaintenanceWorkOrder;
use App\Models\Portfolio;
use App\Models\TenantProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class ActionCenterWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->travelTo('2026-07-25 10:00:00');
    }

    public function test_owner_sees_one_prioritized_queue_without_foreign_portfolio_work(): void
    {
        $portfolio = $this->createPortfolio(['name_en' => 'Owner Portfolio']);
        $foreignPortfolio = $this->createPortfolio(['name_en' => 'Foreign Portfolio']);
        $owner = $this->createUserWithRole('owner', $portfolio);
        $manager = $this->createUserWithRole('property_manager', $portfolio, [
            'name' => 'Operations Manager',
        ]);
        $foreignOwner = $this->createUserWithRole('owner', $foreignPortfolio);
        [$lease, $property, $unit, $tenant] = $this->leaseFixture(
            $portfolio,
            $owner,
            'Owner Tower',
            'Owner Tenant',
            'OWNER-ACTION-LEASE',
        );

        $this->installment($lease, 'Owner overdue rent', today()->subDays(40));
        $maintenance = $this->maintenance(
            $portfolio,
            $unit,
            $tenant,
            $manager,
            'Owner urgent leak',
            'urgent',
            now()->subHour(),
        );
        $vendor = MaintenanceVendor::query()->create([
            'portfolio_id' => $portfolio->id,
            'name' => 'Owner Emergency Services',
            'service_category' => 'electricity',
            'status' => 'active',
        ]);
        $workOrder = MaintenanceWorkOrder::query()->create([
            'portfolio_id' => $portfolio->id,
            'maintenance_request_id' => $maintenance->id,
            'vendor_id' => $vendor->id,
            'created_by_user_id' => $owner->id,
            'assigned_to_user_id' => $manager->id,
            'reference_code' => 'WO-ACTION-001',
            'vendor_name' => $vendor->name,
            'status' => 'scheduled',
            'scheduled_at' => now()->addHour(),
            'currency' => 'SAR',
            'scope' => 'Stop the leak and restore safe electrical service.',
        ]);
        LeaseMoveOut::query()->create([
            'portfolio_id' => $portfolio->id,
            'lease_id' => $lease->id,
            'initiated_by_user_id' => $owner->id,
            'status' => 'planned',
            'move_out_date' => today()->addDays(5),
            'reason' => 'tenant_notice',
            'deposit_disposition' => 'not_applicable',
        ]);

        [$foreignLease, , $foreignUnit, $foreignTenant] = $this->leaseFixture(
            $foreignPortfolio,
            $foreignOwner,
            'Foreign Tower',
            'Foreign Tenant',
            'FOREIGN-ACTION-LEASE',
        );
        $this->installment($foreignLease, 'Foreign overdue rent', today()->subDays(50));
        $this->maintenance(
            $foreignPortfolio,
            $foreignUnit,
            $foreignTenant,
            $foreignOwner,
            'Foreign urgent leak',
            'urgent',
            now()->subHour(),
        );

        $this->actingAs($owner)
            ->get(route('action-center.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/action-center/index')
                ->where('actionItems.total', 4)
                ->where('metrics.total', 4)
                ->where('metrics.critical', 3)
                ->where('metrics.high', 1)
                ->where('metrics.unassigned', 1)
                ->where('actionItems.data', function ($items) use ($property, $workOrder): bool {
                    $rows = collect($items);

                    return $rows->pluck('type')->sort()->values()->all() === [
                        'collection',
                        'maintenance',
                        'move_out',
                        'renewal',
                    ]
                        && $rows->pluck('priority')->take(3)->every(
                            fn (string $priority): bool => $priority === 'critical',
                        )
                        && $rows->every(
                            fn ($row): bool => data_get($row, 'portfolio.name_en')
                                === 'Owner Portfolio',
                        )
                        && $rows->every(
                            fn ($row): bool => data_get($row, 'asset.id') !== null,
                        )
                        && data_get($rows->firstWhere('type', 'maintenance'), 'subtitle')
                            === 'Electricity'
                        && data_get($rows->firstWhere('type', 'maintenance'), 'work_order.reference_code')
                            === 'WO-ACTION-001'
                        && data_get($rows->firstWhere('type', 'maintenance'), 'href')
                            === route('maintenance-work-orders.show', $workOrder, false)
                        && data_get($rows->firstWhere('type', 'maintenance'), 'status')
                            === 'scheduled'
                        && data_get($rows->firstWhere('type', 'move_out'), 'subtitle')
                            === 'Tenant notice'
                        && data_get($rows->firstWhere('type', 'renewal'), 'subtitle')
                            === 'Active'
                        && $rows->contains(
                            fn ($row): bool => data_get($row, 'asset.id') !== $property->id,
                        );
                })
                ->where('counts', fn ($counts): bool => collect($counts)
                    ->firstWhere('type', 'all')['value'] === 4));
    }

    public function test_manager_scope_and_action_filters_do_not_cross_property_assignments(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $manager = $this->createUserWithRole('property_manager', $portfolio, [
            'name' => 'Assigned Manager',
        ]);
        [$assignedLease, $assignedProperty, $assignedUnit, $assignedTenant] = $this->leaseFixture(
            $portfolio,
            $manager,
            'Assigned Tower',
            'Assigned Tenant',
            'ASSIGNED-ACTION',
        );
        [, , $unassignedUnit, $unassignedTenant] = $this->leaseFixture(
            $portfolio,
            $owner,
            'Unassigned Tower',
            'Unassigned Tenant',
            'UNASSIGNED-ACTION',
        );
        $this->assignManagerToAsset($manager, $assignedProperty);
        $assigned = $this->maintenance(
            $portfolio,
            $assignedUnit,
            $assignedTenant,
            $manager,
            'Assigned electrical fault',
            'high',
            now()->addDay(),
        );
        $this->maintenance(
            $portfolio,
            $unassignedUnit,
            $unassignedTenant,
            $owner,
            'Hidden electrical fault',
            'urgent',
            now()->subDay(),
        );

        $this->actingAs($manager)
            ->get(route('action-center.index', [
                'type' => 'maintenance',
                'priority' => 'high',
                'assignee' => 'me',
                'property_id' => $assignedProperty->id,
                'search' => 'electrical',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('actionItems.total', 1)
                ->where('actionItems.data.0.record_id', $assigned->id)
                ->where('actionItems.data.0.assigned_to.id', $manager->id)
                ->where('filters.type', 'maintenance')
                ->where('filters.priority', 'high')
                ->where('filters.assignee', 'me')
                ->where('filters.property_id', $assignedProperty->id)
                ->where('actionItems.data.0.title', 'Assigned electrical fault'));

        $this->assertNotNull($assignedLease);
    }

    public function test_queue_paginates_at_twelve_and_exports_the_filtered_scope_as_xlsx(): void
    {
        $portfolio = $this->createPortfolio();
        $foreignPortfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $foreignOwner = $this->createUserWithRole('owner', $foreignPortfolio);
        [, , $unit, $tenant] = $this->leaseFixture(
            $portfolio,
            $owner,
            'Scale Tower',
            'Scale Tenant',
            'SCALE-ACTION',
        );
        [, , $foreignUnit, $foreignTenant] = $this->leaseFixture(
            $foreignPortfolio,
            $foreignOwner,
            'Foreign Scale Tower',
            'Foreign Scale Tenant',
            'FOREIGN-SCALE-ACTION',
        );

        foreach (range(1, 13) as $index) {
            $this->maintenance(
                $portfolio,
                $unit,
                $tenant,
                $owner,
                sprintf('Scale request %02d', $index),
                'medium',
                now()->addDays($index),
            );
        }
        $this->maintenance(
            $foreignPortfolio,
            $foreignUnit,
            $foreignTenant,
            $foreignOwner,
            'Foreign scale request',
            'medium',
            now()->addDay(),
        );

        $filters = [
            'type' => 'maintenance',
            'search' => 'Scale request',
            'per_page' => 12,
        ];

        $this->actingAs($owner)
            ->get(route('action-center.index', $filters))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('actionItems.total', 13)
                ->has('actionItems.data', 12)
                ->where('actionItems.current_page', 1)
                ->where('actionItems.last_page', 2)
                ->where('actionItems.links', fn ($links): bool => data_get($links, '0.label') === 'Previous'
                    && data_get($links, (count($links) - 1).'.label') === 'Next'));

        $export = $this->actingAs($owner)
            ->get(route('action-center.export', $filters))
            ->assertOk();
        $worksheet = $this->xlsxWorksheetXml($export);

        $this->assertStringContainsString('Scale request 01', $worksheet);
        $this->assertStringContainsString('Scale request 13', $worksheet);
        $this->assertStringNotContainsString('Foreign scale request', $worksheet);
    }

    public function test_arabic_queue_is_translated_and_tenants_are_denied(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenantUser = $this->createUserWithRole('tenant', $portfolio);
        $tenant = $this->createTenantProfile($portfolio, $tenantUser);
        $asset = $this->createAsset($portfolio, [
            'title_en' => 'Arabic Unit',
            'title_ar' => 'وحدة عربية',
        ]);
        $request = $this->maintenance(
            $portfolio,
            $asset,
            $tenant,
            $owner,
            'Arabic service request',
            'urgent',
            now(),
        );

        $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('action-center.index', ['type' => 'maintenance']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('app.locale', 'ar')
                ->where('app.direction', 'rtl')
                ->where('app.translations.nav.action_center', 'مركز الإجراءات')
                ->where('app.translations.action_center.title', 'مركز الإجراءات')
                ->where('app.translations.action_center.type_maintenance', 'الصيانة')
                ->where('actionItems.data.0.record_id', $request->id)
                ->where('actionItems.data.0.subtitle', 'كهرباء')
                ->where('actionItems.data.0.asset.title_ar', 'وحدة عربية'));

        $this->actingAs($tenantUser)
            ->get(route('action-center.index'))
            ->assertForbidden();
        $this->actingAs($tenantUser)
            ->get(route('action-center.export'))
            ->assertForbidden();
    }

    /**
     * @return array{Lease,Asset,Asset,TenantProfile}
     */
    private function leaseFixture(
        Portfolio $portfolio,
        User $manager,
        string $propertyTitle,
        string $tenantName,
        string $code,
    ): array {
        $property = $this->createAsset($portfolio, [
            'asset_type' => 'building',
            'title_en' => $propertyTitle,
            'title_ar' => "عقار {$propertyTitle}",
            'rentable' => false,
        ]);
        $unit = $this->createAsset($portfolio, [
            'parent_id' => $property->id,
            'title_en' => "{$propertyTitle} Unit",
            'title_ar' => "وحدة {$propertyTitle}",
        ]);
        $tenant = $this->createTenantProfile(
            $portfolio,
            $this->createUserWithRole('tenant', $portfolio, [
                'name' => $tenantName,
            ]),
        );
        $lease = $this->createLease(
            $portfolio,
            $tenant,
            $unit,
            $manager,
            [
                'code' => $code,
                'ends_at' => today()->addDays(20),
                'renewal_notice_days' => 30,
            ],
            syncInstallments: false,
        );

        return [$lease, $property, $unit, $tenant];
    }

    private function installment(
        Lease $lease,
        string $label,
        \DateTimeInterface $dueDate,
    ): LeaseInstallment {
        return LeaseInstallment::query()->create([
            'lease_id' => $lease->id,
            'sequence' => 1,
            'line_type' => 'rent',
            'label' => $label,
            'period_start' => today()->subMonth(),
            'period_end' => today(),
            'due_date' => $dueDate,
            'amount_due' => 2000,
            'amount_paid' => 0,
            'status' => 'overdue',
        ]);
    }

    private function maintenance(
        Portfolio $portfolio,
        Asset $asset,
        TenantProfile $tenant,
        User $assignee,
        string $title,
        string $priority,
        \DateTimeInterface $dueAt,
    ): MaintenanceRequest {
        return MaintenanceRequest::query()->create([
            'portfolio_id' => $portfolio->id,
            'asset_id' => $asset->id,
            'tenant_profile_id' => $tenant->id,
            'submitted_by_user_id' => $tenant->user_id,
            'assigned_to_user_id' => $assignee->id,
            'category' => 'electricity',
            'priority' => $priority,
            'status' => 'open',
            'title' => $title,
            'description' => 'Action Center test request.',
            'requested_at' => now(),
            'due_at' => $dueAt,
        ]);
    }
}
