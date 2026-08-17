<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\CmsPage;
use App\Models\Document;
use App\Models\ExpenseEntry;
use App\Models\Lease;
use App\Models\LeaseInstallment;
use App\Models\MaintenanceRequest;
use App\Models\Payment;
use App\Models\Portfolio;
use App\Models\ShowcaseDataset;
use App\Models\TenantProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class DashboardModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_owner_dashboard_returns_only_scoped_posted_activity_and_needed_chart_data(): void
    {
        $portfolio = $this->createPortfolio();
        $foreignPortfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $foreignOwner = $this->createUserWithRole('owner', $foreignPortfolio);
        [$tenant, $lease] = $this->tenantLease($portfolio, $owner);
        [$foreignTenant, $foreignLease] = $this->tenantLease($foreignPortfolio, $foreignOwner);
        $posted = $this->payment($portfolio, $tenant, $lease, $owner, 'OWN-POSTED', 'posted', 700);

        $this->payment($portfolio, $tenant, $lease, $owner, 'OWN-PENDING', 'pending', 900);
        $this->payment($foreignPortfolio, $foreignTenant, $foreignLease, $foreignOwner, 'FOREIGN-POSTED', 'posted', 1200);
        CmsPage::query()->create([
            'slug' => 'private-platform-page',
            'title_en' => 'Platform page',
            'title_ar' => 'صفحة المنصة',
            'status' => 'published',
        ]);

        $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('dashboard')
                ->where('mode', 'portfolio')
                ->where('stats.totalAssets', 1)
                ->where('stats.monthlyRevenue', 700)
                ->where('platformComposition', null)
                ->where('platformActivity', [])
                ->where('cmsStatus', null)
                ->where('readinessStatus', null)
                ->has('recentPayments', 1)
                ->where('recentPayments.0.id', $posted->id)
                ->has('charts.occupancy')
                ->missing('charts.paymentHealth')
                ->missing('charts.assetMix')
                ->missing('charts.maintenanceByStatus'));
    }

    public function test_dashboard_accepts_the_command_center_reporting_period(): void
    {
        $owner = $this->createUserWithRole('owner', $this->createPortfolio());

        $this->actingAs($owner)
            ->get(route('dashboard', ['period' => 'quarter']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('dashboard')
                ->where('period', 'quarter'));

        $this->actingAs($owner)
            ->get(route('dashboard', ['period' => 'invalid']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('period', 'month'));
    }

    public function test_superadmin_dashboard_exposes_platform_only_cms_status(): void
    {
        $superadmin = $this->createUserWithRole('superadmin');
        $livePortfolio = $this->createPortfolio();
        $dataset = ShowcaseDataset::query()->create([
            'key' => 'DASHBOARD-CONTEXT',
            'name' => 'Dashboard context',
            'status' => 'completed',
            'target_properties' => 1,
            'generated_properties' => 1,
        ]);
        $showcasePortfolio = $this->createPortfolio([
            'showcase_dataset_id' => $dataset->id,
        ]);
        $this->createAsset($showcasePortfolio);
        $this->createUserWithRole('owner', $showcasePortfolio, [
            'showcase_dataset_id' => $dataset->id,
        ]);
        $homepage = CmsPage::query()->create([
            'slug' => 'home',
            'title_en' => 'Operations Home',
            'title_ar' => 'الرئيسية التشغيلية',
            'status' => 'published',
            'is_homepage' => true,
        ]);
        CmsPage::query()->create([
            'slug' => 'draft-page',
            'title_en' => 'Draft page',
            'title_ar' => 'صفحة مسودة',
            'status' => 'draft',
        ]);
        $olderLiveActivity = Activity::query()->create([
            'log_name' => 'portfolio',
            'description' => 'updated',
            'subject_type' => 'portfolio',
            'subject_id' => $livePortfolio->id,
            'causer_type' => 'user',
            'causer_id' => $superadmin->id,
            'event' => 'updated',
            'properties' => [],
            'created_at' => now()->addMinute(),
            'updated_at' => now()->addMinute(),
        ]);
        Activity::query()->create([
            'log_name' => 'portfolio',
            'description' => 'updated',
            'subject_type' => 'portfolio',
            'subject_id' => $showcasePortfolio->id,
            'causer_type' => 'user',
            'causer_id' => $superadmin->id,
            'event' => 'updated',
            'properties' => [],
            'created_at' => now()->addMinutes(2),
            'updated_at' => now()->addMinutes(2),
        ]);
        $latestLiveActivity = Activity::query()->create([
            'log_name' => 'portfolio',
            'description' => 'updated',
            'subject_type' => 'portfolio',
            'subject_id' => $livePortfolio->id,
            'causer_type' => 'user',
            'causer_id' => $superadmin->id,
            'event' => 'updated',
            'properties' => [],
            'created_at' => now()->addMinutes(3),
            'updated_at' => now()->addMinutes(3),
        ]);
        $canonicalLiveActivity = Activity::query()->create([
            'log_name' => 'portfolio',
            'description' => 'updated',
            'subject_type' => Portfolio::class,
            'subject_id' => $livePortfolio->id,
            'causer_type' => 'user',
            'causer_id' => $superadmin->id,
            'event' => 'updated',
            'properties' => [],
            'created_at' => now()->addMinutes(4),
            'updated_at' => now()->addMinutes(4),
        ]);

        $this->actingAs($superadmin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('dashboard')
                ->where('mode', 'superadmin')
                ->where('cmsStatus.published', 1)
                ->where('cmsStatus.draft', 1)
                ->where('cmsStatus.homepage', $homepage->title_en)
                ->where('readinessStatus.status', 'blocked')
                ->where('readinessStatus.automatic_blocked', fn (int $count): bool => $count >= 1)
                ->where('readinessStatus.evidence_remaining', 4)
                ->where('readinessStatus.operational_portfolios', 1)
                ->where('readinessStatus.showcase_portfolios', 1)
                ->where('readinessStatus.showcase_assets', 1)
                ->where('readinessStatus.showcase_users', 1)
                ->where('platformComposition.portfolios.live_active', 1)
                ->where('platformComposition.portfolios.showcase', 1)
                ->where('platformComposition.properties.live', 0)
                ->where('platformComposition.properties.showcase', 1)
                ->where('platformActivity.0.id', $canonicalLiveActivity->id)
                ->where('platformActivity.0.subject_id', $livePortfolio->id)
                ->where('platformActivity.0.portfolio.id', $livePortfolio->id)
                ->where(
                    'platformActivity.0.subject_url',
                    route('portfolios.show', $livePortfolio),
                )
                ->where(
                    'platformActivity',
                    fn (mixed $rows): bool => collect($rows)
                        ->doesntContain('id', $olderLiveActivity->id)
                        && collect($rows)->doesntContain('id', $latestLiveActivity->id),
                ));
    }

    public function test_superadmin_company_composition_is_global_and_separates_live_showcase_and_archived_records(): void
    {
        $superadmin = $this->createUserWithRole('superadmin');
        $live = $this->createPortfolio();
        $inactive = $this->createPortfolio(['status' => 'inactive']);
        $archived = $this->createPortfolio(['status' => 'archived']);
        $dataset = ShowcaseDataset::query()->create([
            'key' => 'COMPANY-COMPOSITION',
            'name' => 'Company composition',
            'status' => 'completed',
            'target_properties' => 1,
            'generated_properties' => 1,
        ]);
        $showcase = $this->createPortfolio([
            'showcase_dataset_id' => $dataset->id,
        ]);
        $liveProperty = $this->createAsset($live, [
            'asset_type' => 'building',
            'rentable' => false,
        ]);
        $this->createAsset($live, ['parent_id' => $liveProperty->id]);
        $this->createAsset($inactive, ['asset_type' => 'building']);
        $this->createAsset($archived, ['asset_type' => 'building']);
        $showcaseProperty = $this->createAsset($showcase, [
            'asset_type' => 'building',
            'rentable' => false,
        ]);
        $this->createAsset($showcase, ['parent_id' => $showcaseProperty->id]);

        $this->createUserWithRole('owner', $live);
        $this->createUserWithRole('property_manager', $live);
        $this->createUserWithRole('tenant', $live);
        $this->createUserWithRole('owner', $inactive, ['status' => 'inactive']);
        $this->createUserWithRole('owner', $showcase, [
            'showcase_dataset_id' => $dataset->id,
        ]);

        $this->actingAs($superadmin)
            ->get(route('dashboard', ['property_id' => $liveProperty->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('propertyFocus.selected.id', $liveProperty->id)
                ->where('platformComposition.portfolios.live_active', 1)
                ->where('platformComposition.portfolios.live_inactive', 1)
                ->where('platformComposition.portfolios.live_archived', 1)
                ->where('platformComposition.portfolios.showcase', 1)
                ->where('platformComposition.properties.live', 1)
                ->where('platformComposition.properties.showcase', 1)
                ->where('platformComposition.properties.asset_records', 4)
                ->where('platformComposition.accounts.live_active', 4)
                ->where('platformComposition.accounts.live_inactive', 1)
                ->where('platformComposition.accounts.showcase', 1)
                ->where('platformComposition.accounts.roles.superadmins', 1)
                ->where('platformComposition.accounts.roles.owners', 1)
                ->where('platformComposition.accounts.roles.managers', 1)
                ->where('platformComposition.accounts.roles.tenants', 1));
    }

    public function test_owner_dashboard_returns_actionable_month_and_property_performance_data(): void
    {
        $this->travelTo(now()->setDate(2026, 7, 31)->setTime(12, 0));
        $portfolio = $this->createPortfolio();
        $foreignPortfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $foreignOwner = $this->createUserWithRole('owner', $foreignPortfolio);
        $root = $this->createAsset($portfolio, [
            'asset_type' => 'building',
            'title_en' => 'Operations Tower',
            'rentable' => false,
        ]);
        $unit = $this->createAsset($portfolio, [
            'parent_id' => $root->id,
            'title_en' => 'Operations Unit',
            'occupancy_status' => 'occupied',
        ]);
        $lease = $this->createLease(
            $portfolio,
            $this->createTenantProfile($portfolio, $this->createUserWithRole('tenant', $portfolio)),
            $unit,
            $owner,
            syncInstallments: false,
        );
        $installment = LeaseInstallment::query()->create([
            'lease_id' => $lease->id,
            'sequence' => 1,
            'line_type' => 'rent',
            'label' => 'Current rent',
            'due_date' => now()->subDays(3)->toDateString(),
            'amount_due' => 1000,
            'amount_paid' => 400,
            'status' => 'partial',
        ]);
        $posted = $this->payment($portfolio, $lease->tenantProfile, $lease, $owner, 'MONTH-PAID', 'posted', 400);

        ExpenseEntry::query()->create([
            'portfolio_id' => $portfolio->id,
            'asset_id' => $unit->id,
            'created_by_user_id' => $owner->id,
            'category' => 'general',
            'title' => 'Current expense',
            'incurred_on' => now()->toDateString(),
            'amount' => 100,
            'currency' => 'SAR',
            'status' => 'posted',
        ]);
        $openRequest = MaintenanceRequest::query()->create([
            'portfolio_id' => $portfolio->id,
            'asset_id' => $unit->id,
            'tenant_profile_id' => $lease->tenant_profile_id,
            'submitted_by_user_id' => $owner->id,
            'category' => 'plumbing',
            'priority' => 'high',
            'status' => 'open',
            'title' => 'Open leak',
            'description' => 'Needs service',
            'requested_at' => now(),
        ]);
        MaintenanceRequest::query()->create([
            'portfolio_id' => $portfolio->id,
            'asset_id' => $unit->id,
            'tenant_profile_id' => $lease->tenant_profile_id,
            'submitted_by_user_id' => $owner->id,
            'category' => 'general',
            'priority' => 'low',
            'status' => 'resolved',
            'title' => 'Resolved issue',
            'description' => 'Already complete',
            'requested_at' => now(),
        ]);

        $foreignRoot = $this->createAsset($foreignPortfolio, [
            'asset_type' => 'building',
            'title_en' => 'Foreign Tower',
            'rentable' => false,
        ]);
        $this->createAsset($foreignPortfolio, [
            'parent_id' => $foreignRoot->id,
            'occupancy_status' => 'occupied',
        ]);
        $this->payment(
            $foreignPortfolio,
            $this->createTenantProfile($foreignPortfolio, $this->createUserWithRole('tenant', $foreignPortfolio)),
            $this->createLease(
                $foreignPortfolio,
                $this->createTenantProfile($foreignPortfolio, $this->createUserWithRole('tenant', $foreignPortfolio)),
                $this->createAsset($foreignPortfolio),
                $foreignOwner,
            ),
            $foreignOwner,
            'FOREIGN-MONTH',
            'posted',
            9000,
        );

        $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('financial.scheduledDue', fn (int|float $value) => (float) $value === 1000.0)
                ->where('financial.scheduledPaid', fn (int|float $value) => (float) $value === 400.0)
                ->where('financial.collectionRate', fn (int|float $value) => (float) $value === 40.0)
                ->where('financial.net', fn (int|float $value) => (float) $value === 300.0)
                ->where('financial.overdueInstallments', 1)
                ->where('financial.overdueLeases', 1)
                ->where('collectionQueue.0.id', $installment->id)
                ->where('collectionQueue.0.outstanding_amount', fn (int|float $value) => (float) $value === 600.0)
                ->has('propertyPerformance', 1)
                ->where('propertyPerformance.0.id', $root->id)
                ->where('propertyPerformance.0.collection_rate', fn (int|float $value) => (float) $value === 40.0)
                ->where('propertyPerformance.0.net', fn (int|float $value) => (float) $value === 300.0)
                ->where('propertyPerformance.0.open_requests', 1)
                ->where('recentPayments.0.id', $posted->id)
                ->has('recentMaintenance', 1)
                ->where('recentMaintenance.0.id', $openRequest->id)
                ->where('nextActions.0.href', '/rent-collection?status=overdue'));
    }

    public function test_owner_can_focus_the_entire_dashboard_on_one_authorized_property(): void
    {
        $this->travelTo('2026-08-15 12:00:00');

        $portfolio = $this->createPortfolio();
        $foreignPortfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $foreignOwner = $this->createUserWithRole('owner', $foreignPortfolio);
        $firstRoot = $this->createAsset($portfolio, [
            'asset_type' => 'building',
            'title_en' => 'Alpha Tower',
            'rentable' => false,
            'valuation_amount' => 1000000,
            'meta_json' => ['map' => [
                'latitude' => 24.7136,
                'longitude' => 46.6753,
                'zone_en' => 'Central',
                'zone_ar' => 'الوسط',
                'land_number' => 'A-100',
            ]],
        ]);
        $firstUnit = $this->createAsset($portfolio, [
            'parent_id' => $firstRoot->id,
            'title_en' => 'Alpha Unit',
            'occupancy_status' => 'occupied',
            'valuation_amount' => 200000,
        ]);
        $secondRoot = $this->createAsset($portfolio, [
            'asset_type' => 'building',
            'title_en' => 'Beta Tower',
            'rentable' => false,
            'valuation_amount' => 2000000,
        ]);
        $secondUnit = $this->createAsset($portfolio, [
            'parent_id' => $secondRoot->id,
            'title_en' => 'Beta Unit',
            'occupancy_status' => 'occupied',
            'valuation_amount' => 400000,
        ]);
        $inactiveRoot = $this->createAsset($portfolio, [
            'asset_type' => 'building',
            'title_en' => 'Closed Tower',
            'rentable' => false,
            'status' => 'inactive',
        ]);
        $foreignRoot = $this->createAsset($foreignPortfolio, [
            'asset_type' => 'building',
            'title_en' => 'Foreign Tower',
            'rentable' => false,
        ]);
        $firstTenant = $this->createTenantProfile(
            $portfolio,
            $this->createUserWithRole('tenant', $portfolio),
        );
        $secondTenant = $this->createTenantProfile(
            $portfolio,
            $this->createUserWithRole('tenant', $portfolio),
        );
        $firstLease = $this->createLease(
            $portfolio,
            $firstTenant,
            $firstUnit,
            $owner,
            ['ends_at' => now()->addDays(45)->toDateString()],
            syncInstallments: false,
        );
        $secondLease = $this->createLease(
            $portfolio,
            $secondTenant,
            $secondUnit,
            $owner,
            syncInstallments: false,
        );
        $firstInstallment = LeaseInstallment::query()->create([
            'lease_id' => $firstLease->id,
            'sequence' => 1,
            'line_type' => 'rent',
            'label' => 'Alpha rent',
            'due_date' => now()->subDays(2)->toDateString(),
            'amount_due' => 1000,
            'amount_paid' => 650,
            'status' => 'partial',
        ]);
        LeaseInstallment::query()->create([
            'lease_id' => $secondLease->id,
            'sequence' => 1,
            'line_type' => 'rent',
            'label' => 'Beta rent',
            'due_date' => now()->subDays(2)->toDateString(),
            'amount_due' => 9000,
            'amount_paid' => 0,
            'status' => 'overdue',
        ]);
        $firstPayment = $this->payment(
            $portfolio,
            $firstTenant,
            $firstLease,
            $owner,
            'ALPHA-PAID',
            'posted',
            650,
        );
        $this->payment(
            $portfolio,
            $secondTenant,
            $secondLease,
            $owner,
            'BETA-PAID',
            'posted',
            5000,
        );
        ExpenseEntry::query()->create([
            'portfolio_id' => $portfolio->id,
            'asset_id' => $firstUnit->id,
            'created_by_user_id' => $owner->id,
            'category' => 'general',
            'title' => 'Alpha service',
            'incurred_on' => now()->toDateString(),
            'amount' => 100,
            'currency' => 'SAR',
            'status' => 'posted',
        ]);
        ExpenseEntry::query()->create([
            'portfolio_id' => $portfolio->id,
            'asset_id' => $secondUnit->id,
            'created_by_user_id' => $owner->id,
            'category' => 'general',
            'title' => 'Beta service',
            'incurred_on' => now()->toDateString(),
            'amount' => 900,
            'currency' => 'SAR',
            'status' => 'posted',
        ]);
        $firstRequest = $this->maintenanceRequest(
            $portfolio,
            $firstUnit,
            $firstTenant,
            $owner,
            'Alpha leak',
        );
        $this->maintenanceRequest(
            $portfolio,
            $secondUnit,
            $secondTenant,
            $owner,
            'Beta leak',
        );

        $this->actingAs($owner)
            ->get(route('dashboard', ['property_id' => $firstRoot->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('propertyFocus.selected.id', $firstRoot->id)
                ->where('propertyFocus.property_count', 2)
                ->has('propertyContext.options', 2)
                ->where('propertyContext.options.0.id', $firstRoot->id)
                ->where('propertyContext.options.1.id', $secondRoot->id)
                ->where('stats.totalAssets', 2)
                ->where('stats.totalValue', fn (int|float $value): bool => (float) $value === 1200000.0)
                ->where('stats.activeLeases', 1)
                ->where('stats.monthlyRevenue', fn (int|float $value): bool => (float) $value === 650.0)
                ->where('stats.monthlyExpenses', fn (int|float $value): bool => (float) $value === 100.0)
                ->where('stats.openRequests', 1)
                ->where('stats.arrears', fn (int|float $value): bool => (float) $value === 350.0)
                ->where('financial.scheduledDue', fn (int|float $value): bool => (float) $value === 1000.0)
                ->where('financial.scheduledPaid', fn (int|float $value): bool => (float) $value === 650.0)
                ->where('financial.net', fn (int|float $value): bool => (float) $value === 550.0)
                ->where('financial.overdueInstallments', 1)
                ->where('financial.overdueLeases', 1)
                ->where('charts.occupancy.occupied', 1)
                ->has('propertyPerformance', 1)
                ->where('propertyPerformance.0.id', $firstRoot->id)
                ->has('collectionQueue', 1)
                ->where('collectionQueue.0.id', $firstInstallment->id)
                ->has('expiringLeases', 1)
                ->where('expiringLeases.0.id', $firstLease->id)
                ->has('recentPayments', 1)
                ->where('recentPayments.0.id', $firstPayment->id)
                ->has('recentMaintenance', 1)
                ->where('recentMaintenance.0.id', $firstRequest->id)
                ->where('propertyMap.summary.total', 1)
                ->where('propertyMap.assets.0.id', $firstRoot->id)
                ->where(
                    'nextActions.0.href',
                    '/rent-collection?status=overdue&property_id='.$firstRoot->id,
                )
                ->where(
                    'nextActions.1.href',
                    '/maintenance-requests?status=open&property_id='.$firstRoot->id,
                ));

        $this->actingAs($owner)
            ->get(route('dashboard', [
                'property_id' => $secondRoot->id,
                'locale' => 'ar',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('propertyFocus.selected.id', $secondRoot->id)
                ->where('nextActions.2.href', '/assets/'.$secondRoot->id)
                ->where(
                    'nextActions.2.description',
                    fn (string $value): bool => str_contains($value, 'أصلح')
                        && ! str_contains($value, 'Fix'),
                ));

        $this->actingAs($owner)
            ->get(route('dashboard', ['property_id' => $firstUnit->id]))
            ->assertNotFound();
        $this->actingAs($owner)
            ->get(route('dashboard', ['property_id' => $foreignRoot->id]))
            ->assertNotFound();
        $this->actingAs($owner)
            ->get(route('dashboard', ['property_id' => $inactiveRoot->id]))
            ->assertNotFound();
        $this->actingAs($owner)
            ->get('/dashboard?property_id=invalid')
            ->assertRedirect()
            ->assertSessionHasErrors('property_id');
    }

    public function test_tenant_dashboard_excludes_unposted_payments_and_returns_arabic_document_titles(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenantUser = $this->createUserWithRole('tenant', $portfolio);
        $tenant = $this->createTenantProfile($portfolio, $tenantUser);
        $asset = $this->createAsset($portfolio);
        $lease = $this->createLease($portfolio, $tenant, $asset, $owner);
        $posted = $this->payment($portfolio, $tenant, $lease, $owner, 'VISIBLE', 'posted', 500);
        $this->maintenanceRequest($portfolio, $asset, $tenant, $owner, 'Open issue');
        $awaitingConfirmation = $this->maintenanceRequest(
            $portfolio,
            $asset,
            $tenant,
            $owner,
            'Resolved issue',
        );
        $awaitingConfirmation->update(['status' => 'resolved']);
        $cancelled = $this->maintenanceRequest(
            $portfolio,
            $asset,
            $tenant,
            $owner,
            'Cancelled issue',
        );
        $cancelled->update(['status' => 'cancelled']);

        $this->payment($portfolio, $tenant, $lease, $owner, 'HIDDEN-PENDING', 'pending', 900);
        $document = Document::query()->create([
            'portfolio_id' => $portfolio->id,
            'uploaded_by_user_id' => $owner->id,
            'documentable_type' => $lease->getMorphClass(),
            'documentable_id' => $lease->id,
            'type' => 'lease_contract',
            'title_en' => 'Lease contract',
            'title_ar' => 'عقد الإيجار',
            'disk' => 'local',
            'file_path' => 'documents/leases/contract.pdf',
            'original_name' => 'contract.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 120,
            'is_public' => true,
        ]);

        $this->actingAs($tenantUser)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('dashboard')
                ->where('mode', 'tenant')
                ->where('stats.maintenanceRequests', 1)
                ->where('stats.maintenanceConfirmations', 1)
                ->where('tenantPortal.lease.status', 'active')
                ->has('tenantPortal.payments', 1)
                ->where('tenantPortal.payments.0.id', $posted->id)
                ->where('tenantPortal.documents.0.id', $document->id)
                ->where('tenantPortal.documents.0.title_ar', 'عقد الإيجار')
                ->missing('propertyFocus')
                ->missing('tenantPortal.tenant'));
    }

    public function test_dashboard_property_cards_and_asset_detail_keep_currencies_separate(): void
    {
        $this->travelTo('2026-08-15 12:00:00');

        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $property = $this->createAsset($portfolio, [
            'asset_type' => 'building',
            'rentable' => false,
            'currency' => 'SAR',
        ]);
        $sarUnit = $this->createAsset($portfolio, [
            'parent_id' => $property->id,
            'currency' => 'SAR',
            'occupancy_status' => 'occupied',
        ]);
        $usdUnit = $this->createAsset($portfolio, [
            'parent_id' => $property->id,
            'currency' => 'USD',
            'occupancy_status' => 'occupied',
        ]);
        $sarTenant = $this->createTenantProfile(
            $portfolio,
            $this->createUserWithRole('tenant', $portfolio),
        );
        $usdTenant = $this->createTenantProfile(
            $portfolio,
            $this->createUserWithRole('tenant', $portfolio),
        );
        $sarLease = $this->createLease(
            $portfolio,
            $sarTenant,
            $sarUnit,
            $owner,
            ['currency' => 'SAR'],
            false,
        );
        $usdLease = $this->createLease(
            $portfolio,
            $usdTenant,
            $usdUnit,
            $owner,
            ['currency' => 'USD'],
            false,
        );

        foreach ([
            [$sarLease, 1000, 400],
            [$usdLease, 200, 50],
        ] as [$lease, $due, $paid]) {
            LeaseInstallment::query()->create([
                'lease_id' => $lease->id,
                'sequence' => 1,
                'line_type' => 'rent',
                'label' => 'Current rent',
                'due_date' => now()->subDay()->toDateString(),
                'amount_due' => $due,
                'amount_paid' => $paid,
                'status' => 'partial',
            ]);
        }

        foreach ([
            [$sarLease, $sarTenant, 'SAR-DASH', 400, 'SAR'],
            [$usdLease, $usdTenant, 'USD-DASH', 50, 'USD'],
        ] as [$lease, $tenant, $reference, $amount, $currency]) {
            Payment::query()->create([
                'portfolio_id' => $portfolio->id,
                'lease_id' => $lease->id,
                'tenant_profile_id' => $tenant->id,
                'recorded_by_user_id' => $owner->id,
                'reference' => $reference,
                'type' => 'rent',
                'method' => 'cash',
                'status' => 'posted',
                'received_on' => now()->toDateString(),
                'amount' => $amount,
                'currency' => $currency,
            ]);
        }

        foreach ([
            [$sarUnit, 100, 'SAR'],
            [$usdUnit, 20, 'USD'],
        ] as [$asset, $amount, $currency]) {
            ExpenseEntry::query()->create([
                'portfolio_id' => $portfolio->id,
                'asset_id' => $asset->id,
                'created_by_user_id' => $owner->id,
                'category' => 'general',
                'title' => "{$currency} expense",
                'incurred_on' => now()->toDateString(),
                'amount' => $amount,
                'currency' => $currency,
                'status' => 'posted',
            ]);
        }

        $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('financial.currency', null)
                ->where('financial.currencyCount', 2)
                ->where('financial.revenue', null)
                ->where('financial.expenses', null)
                ->where('financial.net', null)
                ->where('financial.arrears', null)
                ->where('financial.overdueInstallments', 2)
                ->where('financial.overdueLeases', 2)
                ->where('financial.hasArrears', true)
                ->where('stats.monthlyRevenue', null)
                ->where('stats.monthlyExpenses', null)
                ->where('stats.arrears', null)
                ->where('stats.hasArrears', true)
                ->where('propertyPerformance.0.currency', null)
                ->where('propertyPerformance.0.currency_count', 2)
                ->where('propertyPerformance.0.net', null)
                ->where(
                    'financial.currencyTotals',
                    fn ($positions): bool => collect($positions)->contains(
                        fn (array $position): bool => $position['currency'] === 'SAR'
                            && (float) $position['revenue'] === 400.0
                            && (float) $position['expenses'] === 100.0
                            && (float) $position['arrears'] === 600.0,
                    ) && collect($positions)->contains(
                        fn (array $position): bool => $position['currency'] === 'USD'
                            && (float) $position['revenue'] === 50.0
                            && (float) $position['expenses'] === 20.0
                            && (float) $position['arrears'] === 150.0,
                    ),
                ));

        $this->actingAs($owner)
            ->get(route('assets.show', $property))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('detailPage.workflow.status', '600.00 SAR · 150.00 USD'));

        $this->actingAs($owner)
            ->get(route('portfolio-control.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('summary.currency', null)
                ->where('summary.currency_count', 2)
                ->where('summary.arrears', null)
                ->where('summary.net', null)
                ->where('properties.data.0.currency', null)
                ->where('properties.data.0.currency_count', 2)
                ->has('summary.currency_totals', 2));
    }

    private function maintenanceRequest(
        Portfolio $portfolio,
        Asset $asset,
        TenantProfile $tenant,
        User $owner,
        string $title,
    ): MaintenanceRequest {
        return MaintenanceRequest::query()->create([
            'portfolio_id' => $portfolio->id,
            'asset_id' => $asset->id,
            'tenant_profile_id' => $tenant->id,
            'submitted_by_user_id' => $owner->id,
            'category' => 'plumbing',
            'priority' => 'high',
            'status' => 'open',
            'title' => $title,
            'description' => 'Needs service',
            'requested_at' => now(),
        ]);
    }

    /** @return array{TenantProfile, Lease} */
    private function tenantLease(Portfolio $portfolio, User $owner): array
    {
        $tenant = $this->createTenantProfile(
            $portfolio,
            $this->createUserWithRole('tenant', $portfolio),
        );

        return [$tenant, $this->createLease($portfolio, $tenant, $this->createAsset($portfolio), $owner)];
    }

    private function payment(
        Portfolio $portfolio,
        TenantProfile $tenant,
        Lease $lease,
        User $owner,
        string $reference,
        string $status,
        float $amount,
    ): Payment {
        return Payment::query()->create([
            'portfolio_id' => $portfolio->id,
            'lease_id' => $lease->id,
            'tenant_profile_id' => $tenant->id,
            'recorded_by_user_id' => $owner->id,
            'reference' => $reference,
            'type' => 'rent',
            'method' => 'cash',
            'status' => $status,
            'received_on' => now()->toDateString(),
            'amount' => $amount,
            'currency' => 'SAR',
        ]);
    }
}
