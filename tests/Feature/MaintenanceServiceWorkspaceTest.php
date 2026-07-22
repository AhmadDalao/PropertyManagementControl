<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\ExpenseEntry;
use App\Models\MaintenanceRequest;
use App\Modules\Maintenance\Actions\ManageMaintenance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class MaintenanceServiceWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_manager_queue_exposes_operational_summary_without_detail_internals(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $manager = $this->createUserWithRole('property_manager', $portfolio, ['name' => 'Service Manager']);
        $tenantUser = $this->createUserWithRole('tenant', $portfolio, ['name' => 'Maintenance Tenant']);
        $tenant = $this->createTenantProfile($portfolio, $tenantUser);
        $asset = $this->createAsset($portfolio, ['title_en' => 'Service Unit']);

        $requestItem = MaintenanceRequest::query()->create([
            'portfolio_id' => $portfolio->id,
            'asset_id' => $asset->id,
            'tenant_profile_id' => $tenant->id,
            'submitted_by_user_id' => $tenantUser->id,
            'assigned_to_user_id' => $manager->id,
            'category' => 'plumbing',
            'priority' => 'high',
            'status' => 'in_progress',
            'title' => 'Kitchen leak',
            'description' => 'Water below sink.',
            'internal_notes' => 'Call vendor before visiting.',
            'requested_at' => now()->subDay(),
            'due_at' => now()->addDay(),
        ]);

        $requestItem->updates()->create([
            'user_id' => $tenantUser->id,
            'status_to' => 'open',
            'is_public_comment' => true,
            'comment' => 'Tenant opened request.',
            'created_at' => now()->subHours(3),
        ]);
        $requestItem->updates()->create([
            'user_id' => $owner->id,
            'status_from' => 'open',
            'status_to' => 'in_progress',
            'is_public_comment' => false,
            'comment' => 'Internal vendor assigned.',
            'created_at' => now()->subHour(),
        ]);

        ExpenseEntry::query()->create([
            'portfolio_id' => $portfolio->id,
            'asset_id' => $asset->id,
            'maintenance_request_id' => $requestItem->id,
            'created_by_user_id' => $owner->id,
            'category' => 'plumbing',
            'title' => 'Leak parts',
            'incurred_on' => now()->toDateString(),
            'amount' => 350,
            'currency' => 'SAR',
            'status' => 'posted',
        ]);

        $this->actingAs($owner)
            ->get(route('maintenance-requests.index', ['search' => 'Kitchen leak']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/maintenance/index')
                ->where('mode', 'manager')
                ->where('requests.total', 1)
                ->where('requests.data.0.title', 'Kitchen leak')
                ->where('requests.data.0.assigned_to.name', 'Service Manager')
                ->where('requests.data.0.expense_total', 350)
                ->where('requests.data.0.is_overdue', false)
                ->where('requests.data.0', fn ($row) => collect($row)->only([
                    'description',
                    'internal_notes',
                    'updates',
                ])->isEmpty())
                ->where('maintenanceInsights.total', 1)
                ->where('maintenanceInsights.in_progress', 1)
                ->where('maintenanceInsights.posted_expenses', 350)
                ->has('categoryOptions', 4)
                ->has('priorityOptions', 4)
                ->has('statusOptions', 4));

        $this->actingAs($owner)
            ->get(route('maintenance-requests.show', $requestItem))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('detailPage.stats', fn ($stats) => collect($stats)->contains('label', 'Posted cost'))
                ->where('detailPage.sections.0.items', fn ($items) => collect($items)->contains(
                    fn ($item) => $item['label'] === 'Internal notes'
                        && $item['value'] === 'Call vendor before visiting.'
                ))
                ->where('detailPage.related', fn ($related) => collect($related)->pluck('title')->all() === [
                    'Updates',
                    'Expenses',
                ])
                ->where('detailPage.timeline', fn ($timeline) => collect($timeline)->isNotEmpty()));
    }

    public function test_tenant_request_uses_the_active_lease_for_the_selected_asset(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenantUser = $this->createUserWithRole('tenant', $portfolio);
        $tenant = $this->createTenantProfile($portfolio, $tenantUser);
        $firstAsset = $this->createAsset($portfolio, ['title_en' => 'First rented unit']);
        $secondAsset = $this->createAsset($portfolio, ['title_en' => 'Second rented unit']);
        $this->createLease($portfolio, $tenant, $firstAsset, $owner, ['code' => 'LEASE-FIRST']);
        $secondLease = $this->createLease($portfolio, $tenant, $secondAsset, $owner, ['code' => 'LEASE-SECOND']);
        $secondLease->update(['leaseable_type' => (new Asset)->getMorphClass()]);

        $response = $this->actingAs($tenantUser)
            ->post(route('maintenance-requests.store'), [
                'asset_id' => $secondAsset->id,
                'category' => 'electricity',
                'priority' => 'high',
                'title' => 'Panel sparks',
                'description' => 'The electrical panel sparks when AC starts.',
            ]);

        $requestItem = MaintenanceRequest::query()
            ->where('title', 'Panel sparks')
            ->firstOrFail();

        $response->assertRedirect(route('maintenance-requests.show', $requestItem));

        $this->assertDatabaseHas('maintenance_requests', [
            'asset_id' => $secondAsset->id,
            'lease_id' => $secondLease->id,
            'tenant_profile_id' => $tenant->id,
            'title' => 'Panel sparks',
        ]);
    }

    public function test_manager_update_preserves_due_date_until_priority_changes(): void
    {
        $this->travelTo('2026-01-15 10:00:00');

        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $manager = $this->createUserWithRole('property_manager', $portfolio);
        $tenantUser = $this->createUserWithRole('tenant', $portfolio);
        $tenant = $this->createTenantProfile($portfolio, $tenantUser);
        $asset = $this->createAsset($portfolio);

        $requestItem = MaintenanceRequest::query()->create([
            'portfolio_id' => $portfolio->id,
            'asset_id' => $asset->id,
            'tenant_profile_id' => $tenant->id,
            'submitted_by_user_id' => $tenantUser->id,
            'assigned_to_user_id' => $manager->id,
            'category' => 'general',
            'priority' => 'medium',
            'status' => 'open',
            'title' => 'Door lock issue',
            'description' => 'Main lock is hard to turn.',
            'requested_at' => now(),
            'due_at' => now()->addDays(4),
        ]);

        $this->travelTo('2026-01-15 12:00:00');

        $this->actingAs($owner)
            ->put(route('maintenance-requests.update', $requestItem), [
                'assigned_to_user_id' => $manager->id,
                'priority' => 'medium',
                'status' => 'in_progress',
                'internal_notes' => 'Vendor contacted.',
            ])
            ->assertRedirect(route('maintenance-requests.show', $requestItem));

        $requestItem->refresh();
        $this->assertSame('2026-01-19 10:00:00', $requestItem->due_at->toDateTimeString());

        $this->travelTo('2026-01-15 13:00:00');

        $this->actingAs($owner)
            ->put(route('maintenance-requests.update', $requestItem), [
                'assigned_to_user_id' => $manager->id,
                'priority' => 'urgent',
                'status' => 'in_progress',
                'internal_notes' => 'Escalated.',
            ])
            ->assertRedirect(route('maintenance-requests.show', $requestItem));

        $requestItem->refresh();
        $this->assertSame('2026-01-16 13:00:00', $requestItem->due_at->toDateTimeString());
    }

    public function test_manager_cannot_assign_service_request_to_tenant_user(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenantUser = $this->createUserWithRole('tenant', $portfolio);
        $tenant = $this->createTenantProfile($portfolio, $tenantUser);
        $asset = $this->createAsset($portfolio);

        $requestItem = MaintenanceRequest::query()->create([
            'portfolio_id' => $portfolio->id,
            'asset_id' => $asset->id,
            'tenant_profile_id' => $tenant->id,
            'submitted_by_user_id' => $tenantUser->id,
            'category' => 'plumbing',
            'priority' => 'medium',
            'status' => 'open',
            'title' => 'Sink blocked',
            'description' => 'Kitchen sink drains slowly.',
            'requested_at' => now(),
            'due_at' => now()->addDays(4),
        ]);

        $this->actingAs($owner)
            ->put(route('maintenance-requests.update', $requestItem), [
                'assigned_to_user_id' => $tenantUser->id,
                'priority' => 'medium',
                'status' => 'in_progress',
                'internal_notes' => 'Should not assign to tenant.',
            ])
            ->assertStatus(422);
    }

    public function test_tenant_queue_only_exposes_public_timeline_and_allows_public_comment(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenantUser = $this->createUserWithRole('tenant', $portfolio);
        $tenant = $this->createTenantProfile($portfolio, $tenantUser);
        $asset = $this->createAsset($portfolio);
        $this->createLease($portfolio, $tenant, $asset, $owner);

        $requestItem = MaintenanceRequest::query()->create([
            'portfolio_id' => $portfolio->id,
            'asset_id' => $asset->id,
            'tenant_profile_id' => $tenant->id,
            'submitted_by_user_id' => $tenantUser->id,
            'category' => 'electricity',
            'priority' => 'medium',
            'status' => 'open',
            'title' => 'Breaker issue',
            'description' => 'Breaker keeps tripping.',
            'internal_notes' => 'Do not show tenant.',
            'requested_at' => now(),
            'due_at' => now()->addDays(4),
        ]);

        $requestItem->updates()->create([
            'user_id' => $owner->id,
            'status_to' => 'open',
            'is_public_comment' => false,
            'comment' => 'Internal note.',
        ]);
        $requestItem->updates()->create([
            'user_id' => $owner->id,
            'status_to' => 'open',
            'is_public_comment' => true,
            'comment' => 'We will visit tomorrow.',
        ]);
        ExpenseEntry::query()->create([
            'portfolio_id' => $portfolio->id,
            'asset_id' => $asset->id,
            'maintenance_request_id' => $requestItem->id,
            'created_by_user_id' => $owner->id,
            'category' => 'electricity',
            'title' => 'Private repair cost',
            'incurred_on' => now()->toDateString(),
            'amount' => 900,
            'currency' => 'SAR',
            'status' => 'posted',
        ]);

        $this->actingAs($tenantUser)
            ->get(route('maintenance-requests.index', ['search' => 'Breaker']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/maintenance/index')
                ->where('mode', 'tenant')
                ->where('requests.total', 1)
                ->where('requests.data.0', fn ($row) => collect($row)->only([
                    'description',
                    'internal_notes',
                    'updates',
                ])->isEmpty())
                ->where('requests.data.0.expense_total', 0)
                ->where('requests.data.0.expense_count', 0)
                ->where('maintenanceInsights.posted_expenses', 0));

        $this->actingAs($tenantUser)
            ->get(route('maintenance-requests.show', $requestItem))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/resource-show')
                ->where('detailPage.stats', fn ($stats) => ! collect($stats)->contains('label', 'Cost'))
                ->where('detailPage.related', fn ($related) => collect($related)->count() === 1
                    && collect($related)->first()['title'] === 'Updates'
                    && count(collect($related)->first()['rows']) === 1
                    && collect($related)->first()['rows'][0]['Comment'] === 'We will visit tomorrow.')
                ->where('detailPage.sections.0.items', fn ($items) => ! collect($items)->contains('label', 'Internal notes'))
                ->where('detailPage.timeline', []));

        $this->actingAs($tenantUser)
            ->put(route('maintenance-requests.update', $requestItem), [
                'comment' => 'The breaker panel is accessible after 5 PM.',
            ])
            ->assertRedirect(route('maintenance-requests.show', $requestItem));

        $this->assertDatabaseHas('maintenance_updates', [
            'maintenance_request_id' => $requestItem->id,
            'user_id' => $tenantUser->id,
            'is_public_comment' => true,
            'comment' => 'The breaker panel is accessible after 5 PM.',
        ]);
    }

    public function test_manager_can_share_a_public_status_update_with_tenant(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenantUser = $this->createUserWithRole('tenant', $portfolio);
        $tenant = $this->createTenantProfile($portfolio, $tenantUser);
        $asset = $this->createAsset($portfolio);

        $requestItem = MaintenanceRequest::query()->create([
            'portfolio_id' => $portfolio->id,
            'asset_id' => $asset->id,
            'tenant_profile_id' => $tenant->id,
            'submitted_by_user_id' => $tenantUser->id,
            'category' => 'ac',
            'priority' => 'urgent',
            'status' => 'open',
            'title' => 'AC stopped',
            'description' => 'No cooling.',
            'requested_at' => now(),
            'due_at' => now()->addDay(),
        ]);

        $this->actingAs($owner)
            ->put(route('maintenance-requests.update', $requestItem), [
                'assigned_to_user_id' => $owner->id,
                'priority' => 'urgent',
                'status' => 'in_progress',
                'internal_notes' => 'Vendor booked.',
                'comment' => 'Technician is scheduled for today.',
                'is_public_comment' => true,
            ])
            ->assertRedirect(route('maintenance-requests.show', $requestItem));

        $requestItem->refresh();
        $this->assertSame('in_progress', $requestItem->status);
        $this->assertNotNull($requestItem->due_at);
        $this->assertDatabaseHas('maintenance_updates', [
            'maintenance_request_id' => $requestItem->id,
            'is_public_comment' => true,
            'comment' => 'Technician is scheduled for today.',
        ]);

        $this->actingAs($tenantUser)
            ->get(route('maintenance-requests.show', $requestItem))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('detailPage.related.0.rows.0.Comment', 'Technician is scheduled for today.'));
    }

    public function test_maintenance_action_rejects_cross_portfolio_mutation_when_reused_directly(): void
    {
        $ownerPortfolio = $this->createPortfolio();
        $foreignPortfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $ownerPortfolio);
        $foreignTenantUser = $this->createUserWithRole('tenant', $foreignPortfolio);
        $foreignTenant = $this->createTenantProfile($foreignPortfolio, $foreignTenantUser);
        $foreignAsset = $this->createAsset($foreignPortfolio);
        $foreignRequest = MaintenanceRequest::query()->create([
            'portfolio_id' => $foreignPortfolio->id,
            'asset_id' => $foreignAsset->id,
            'tenant_profile_id' => $foreignTenant->id,
            'submitted_by_user_id' => $foreignTenantUser->id,
            'category' => 'general',
            'priority' => 'medium',
            'status' => 'open',
            'title' => 'Foreign request',
            'description' => 'Must remain isolated.',
            'requested_at' => now(),
        ]);

        try {
            app(ManageMaintenance::class)->cancel($owner, $foreignRequest);
            $this->fail('Cross-portfolio maintenance mutation was not rejected.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }

        $this->assertSame('open', $foreignRequest->fresh()->status);
    }

    public function test_manager_insights_use_scoped_sql_aggregates_and_posted_costs_only(): void
    {
        $this->travelTo('2026-07-22 12:00:00');
        $portfolio = $this->createPortfolio();
        $foreignPortfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenantUser = $this->createUserWithRole('tenant', $portfolio);
        $tenant = $this->createTenantProfile($portfolio, $tenantUser);
        $asset = $this->createAsset($portfolio);

        $open = $this->maintenanceRecord($portfolio->id, $asset->id, $tenant->id, $tenantUser->id, [
            'priority' => 'urgent',
            'status' => 'open',
            'due_at' => now()->subHour(),
        ]);
        $this->maintenanceRecord($portfolio->id, $asset->id, $tenant->id, $tenantUser->id, [
            'status' => 'in_progress',
            'assigned_to_user_id' => $owner->id,
        ]);
        $this->maintenanceRecord($portfolio->id, $asset->id, $tenant->id, $tenantUser->id, [
            'status' => 'resolved',
            'due_at' => now()->subDay(),
        ]);
        $this->maintenanceRecord($portfolio->id, $asset->id, $tenant->id, $tenantUser->id, [
            'status' => 'cancelled',
        ]);

        foreach ([['posted', 125], ['pending', 900]] as [$status, $amount]) {
            ExpenseEntry::query()->create([
                'portfolio_id' => $portfolio->id,
                'asset_id' => $asset->id,
                'maintenance_request_id' => $open->id,
                'created_by_user_id' => $owner->id,
                'category' => 'repairs',
                'title' => $status.' repair',
                'incurred_on' => now()->toDateString(),
                'amount' => $amount,
                'currency' => 'SAR',
                'status' => $status,
            ]);
        }

        $foreignOwner = $this->createUserWithRole('owner', $foreignPortfolio);
        $foreignTenantUser = $this->createUserWithRole('tenant', $foreignPortfolio);
        $foreignTenant = $this->createTenantProfile($foreignPortfolio, $foreignTenantUser);
        $foreignAsset = $this->createAsset($foreignPortfolio);
        $this->maintenanceRecord(
            $foreignPortfolio->id,
            $foreignAsset->id,
            $foreignTenant->id,
            $foreignOwner->id,
            ['priority' => 'urgent', 'due_at' => now()->subDay()],
        );

        $this->actingAs($owner)
            ->get(route('maintenance-requests.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('maintenanceInsights.total', 4)
                ->where('maintenanceInsights.open', 1)
                ->where('maintenanceInsights.in_progress', 1)
                ->where('maintenanceInsights.resolved', 1)
                ->where('maintenanceInsights.cancelled', 1)
                ->where('maintenanceInsights.urgent', 1)
                ->where('maintenanceInsights.overdue', 1)
                ->where('maintenanceInsights.unassigned', 1)
                ->where('maintenanceInsights.posted_expenses', 125));
    }

    public function test_arabic_create_and_detail_pages_use_structured_maintenance_copy(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenantUser = $this->createUserWithRole('tenant', $portfolio);
        $tenant = $this->createTenantProfile($portfolio, $tenantUser);
        $asset = $this->createAsset($portfolio, [
            'title_en' => 'Service unit',
            'title_ar' => 'وحدة الخدمة',
        ]);
        $requestItem = $this->maintenanceRecord(
            $portfolio->id,
            $asset->id,
            $tenant->id,
            $tenantUser->id,
        );

        $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('maintenance-requests.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('app.locale', 'ar')
                ->where('formPage.title', 'إنشاء طلب')
                ->where('formPage.fields', fn ($fields) => collect($fields)->pluck('label')->contains('الأصل')
                    && collect($fields)->pluck('label')->contains('المستأجر')
                    && collect($fields)->pluck('label')->contains('وصف المشكلة')));

        $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('maintenance-requests.show', $requestItem))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('detailPage.header.eyebrow', 'طلب صيانة')
                ->where('detailPage.header.backLabel', 'قائمة الصيانة')
                ->where('detailPage.sections.0.title', 'سياق الطلب')
                ->where('detailPage.related.0.title', 'التحديثات')
                ->where('detailPage.related.1.title', 'المصاريف'));
    }

    public function test_terminal_request_cannot_be_cancelled_twice(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenantUser = $this->createUserWithRole('tenant', $portfolio);
        $tenant = $this->createTenantProfile($portfolio, $tenantUser);
        $asset = $this->createAsset($portfolio);
        $requestItem = $this->maintenanceRecord(
            $portfolio->id,
            $asset->id,
            $tenant->id,
            $tenantUser->id,
            ['status' => 'cancelled'],
        );

        $this->assertFalse(app(ManageMaintenance::class)->cancel($owner, $requestItem));
        $this->assertSame(0, $requestItem->updates()->count());
    }

    /** @param array<string, mixed> $attributes */
    private function maintenanceRecord(
        int $portfolioId,
        int $assetId,
        int $tenantId,
        int $submittedBy,
        array $attributes = [],
    ): MaintenanceRequest {
        return MaintenanceRequest::query()->create([
            'portfolio_id' => $portfolioId,
            'asset_id' => $assetId,
            'tenant_profile_id' => $tenantId,
            'submitted_by_user_id' => $submittedBy,
            'category' => 'general',
            'priority' => 'medium',
            'status' => 'open',
            'title' => 'Service request',
            'description' => 'Service request details.',
            'requested_at' => now(),
            'due_at' => now()->addDays(4),
            ...$attributes,
        ]);
    }
}
