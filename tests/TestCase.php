<?php

namespace Tests;

use App\Models\Asset;
use App\Models\Lease;
use App\Models\Portfolio;
use App\Models\TenantProfile;
use App\Models\User;
use App\Modules\Leases\Actions\InstallmentSchedule;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use ZipArchive;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    protected function createPortfolio(array $attributes = []): Portfolio
    {
        $token = Str::lower(Str::random(10));

        return Portfolio::query()->create(array_merge([
            'name_en' => "Portfolio {$token}",
            'name_ar' => "محفظة {$token}",
            'code' => Str::upper("PF{$token}"),
            'slug' => "portfolio-{$token}",
            'status' => 'active',
            'country' => 'Saudi Arabia',
            'default_currency' => 'SAR',
        ], $attributes));
    }

    protected function createUserWithRole(string $role, ?Portfolio $portfolio = null, array $attributes = []): User
    {
        $user = User::factory()->create(array_merge([
            'portfolio_id' => $portfolio?->id,
            'preferred_locale' => 'en',
            'status' => 'active',
        ], $attributes));

        $user->assignRole($role);

        return $user;
    }

    protected function createTenantProfile(Portfolio $portfolio, User $user, array $attributes = []): TenantProfile
    {
        return TenantProfile::query()->create(array_merge([
            'portfolio_id' => $portfolio->id,
            'user_id' => $user->id,
            'profile_type' => 'individual',
            'status' => 'active',
        ], $attributes));
    }

    protected function createAsset(Portfolio $portfolio, array $attributes = []): Asset
    {
        $token = Str::lower(Str::random(10));

        return Asset::query()->create(array_merge([
            'portfolio_id' => $portfolio->id,
            'asset_type' => 'unit',
            'usage_type' => 'residential',
            'title_en' => "Asset {$token}",
            'title_ar' => "أصل {$token}",
            'code' => Str::upper("AS{$token}"),
            'status' => 'active',
            'occupancy_status' => 'vacant',
            'rentable' => true,
            'valuation_amount' => 250000,
            'currency' => 'SAR',
        ], $attributes));
    }

    protected function createLease(
        Portfolio $portfolio,
        TenantProfile $tenantProfile,
        Asset $asset,
        ?User $manager = null,
        array $attributes = [],
        bool $syncInstallments = true,
    ): Lease {
        $token = Str::upper(Str::random(8));

        $lease = Lease::query()->create(array_merge([
            'portfolio_id' => $portfolio->id,
            'tenant_profile_id' => $tenantProfile->id,
            'managed_by_user_id' => $manager?->id,
            'leaseable_type' => Asset::class,
            'leaseable_id' => $asset->id,
            'code' => "LEASE-{$token}",
            'status' => 'active',
            'payment_frequency' => 'monthly',
            'started_at' => now()->startOfMonth()->toDateString(),
            'ends_at' => now()->startOfMonth()->addMonths(2)->subDay()->toDateString(),
            'renewal_notice_days' => 30,
            'rent_amount' => 2000,
            'deposit_amount' => 1000,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'currency' => 'SAR',
        ], $attributes));

        if ($syncInstallments) {
            app(InstallmentSchedule::class)->sync($lease);
        }

        return $lease->fresh(['installments', 'tenantProfile.user', 'leaseable']);
    }

    protected function xlsxWorksheetXml(TestResponse $response): string
    {
        $this->assertStringContainsString(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            (string) $response->headers->get('content-type'),
        );
        $this->assertStringContainsString('.xlsx', (string) $response->headers->get('content-disposition'));

        $path = $response->baseResponse->getFile()->getPathname();
        $this->assertSame('PK', substr((string) file_get_contents($path), 0, 2));

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path));
        $sheetXml = (string) $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        return $sheetXml;
    }

    protected function fakePdf(string $name = 'document.pdf'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $name,
            "%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF",
        );
    }
}
