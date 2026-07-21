<?php

namespace Tests\Feature;

use App\Models\CmsPage;
use App\Models\Document;
use App\Models\Lease;
use App\Models\Payment;
use App\Models\Portfolio;
use App\Models\TenantProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
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
                ->where('cmsStatus', null)
                ->has('recentPayments', 1)
                ->where('recentPayments.0.id', $posted->id)
                ->has('charts.occupancy')
                ->missing('charts.paymentHealth')
                ->missing('charts.assetMix')
                ->missing('charts.maintenanceByStatus'));
    }

    public function test_superadmin_dashboard_exposes_platform_only_cms_status(): void
    {
        $superadmin = $this->createUserWithRole('superadmin');
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

        $this->actingAs($superadmin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('dashboard')
                ->where('mode', 'superadmin')
                ->where('cmsStatus.published', 1)
                ->where('cmsStatus.draft', 1)
                ->where('cmsStatus.homepage', $homepage->title_en));
    }

    public function test_tenant_dashboard_excludes_unposted_payments_and_returns_arabic_document_titles(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenantUser = $this->createUserWithRole('tenant', $portfolio);
        $tenant = $this->createTenantProfile($portfolio, $tenantUser);
        $lease = $this->createLease($portfolio, $tenant, $this->createAsset($portfolio), $owner);
        $posted = $this->payment($portfolio, $tenant, $lease, $owner, 'VISIBLE', 'posted', 500);

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
                ->has('tenantPortal.payments', 1)
                ->where('tenantPortal.payments.0.id', $posted->id)
                ->where('tenantPortal.documents.0.id', $document->id)
                ->where('tenantPortal.documents.0.title_ar', 'عقد الإيجار')
                ->missing('tenantPortal.tenant'));
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
