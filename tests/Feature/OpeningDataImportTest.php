<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Lease;
use App\Models\Payment;
use App\Models\TenantProfile;
use App\Models\User;
use App\Modules\Exports\Support\XlsxWorkbook;
use App\Modules\OpeningData\Support\OpeningDataWorkbookSchema;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;
use ZipArchive;

class OpeningDataImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Storage::fake('local');
    }

    public function test_only_superadmin_and_owner_can_open_the_import_workspace(): void
    {
        $portfolio = $this->createPortfolio();
        $superadmin = $this->createUserWithRole('superadmin');
        $owner = $this->createUserWithRole('owner', $portfolio);
        $manager = $this->createUserWithRole('property_manager', $portfolio);
        $tenant = $this->createUserWithRole('tenant', $portfolio);

        $this->actingAs($superadmin)
            ->get(route('opening-data.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/opening-data/index')
                ->has('openingData.portfolios', 1)
            );

        $this->actingAs($owner)
            ->get(route('opening-data.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('openingData.portfolios.0.id', $portfolio->id)
            );

        $this->actingAs($manager)
            ->get(route('opening-data.index'))
            ->assertForbidden();
        $this->actingAs($tenant)
            ->get(route('opening-data.index'))
            ->assertForbidden();
    }

    public function test_template_is_a_real_multi_sheet_xlsx_workbook(): void
    {
        $superadmin = $this->createUserWithRole('superadmin');
        $response = $this->actingAs($superadmin)
            ->get(route('opening-data.template'))
            ->assertOk();
        $path = $response->baseResponse->getFile()->getPathname();
        $this->assertSame('PK', substr((string) file_get_contents($path), 0, 2));

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path));
        $workbook = (string) $zip->getFromName('xl/workbook.xml');
        $instructions = (string) $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        foreach (['Instructions', 'Assets', 'Tenants', 'Leases', 'Payments'] as $sheet) {
            $this->assertStringContainsString('name="'.$sheet.'"', $workbook);
        }

        $this->assertStringContainsString('Uploading only previews data', $instructions);
        $this->assertStringContainsString('يعرض رفع الملف معاينة فقط', $instructions);
    }

    public function test_owner_can_preview_and_atomically_import_a_complete_opening_position(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $portfolio->update(['owner_user_id' => $owner->id]);
        $token = $this->previewToken($owner, $portfolio->id, $this->validWorkbook());

        $this->actingAs($owner)
            ->get(route('opening-data.index', ['preview' => $token]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('openingData.preview.ready', true)
                ->where('openingData.preview.issue_count', 0)
                ->where('openingData.preview.counts.Assets', 3)
                ->where('openingData.preview.counts.Tenants', 1)
                ->where('openingData.preview.counts.Leases', 1)
                ->where('openingData.preview.counts.Payments', 1)
            );

        $this->actingAs($owner)
            ->post(route('opening-data.store'), [
                'preview_token' => $token,
                'confirmed' => true,
            ])
            ->assertRedirect(route('opening-data.index'))
            ->assertSessionHas('success');

        $building = Asset::query()->where('code', 'OPEN-BLDG-01')->firstOrFail();
        $floor = Asset::query()->where('code', 'OPEN-FLR-01')->firstOrFail();
        $unit = Asset::query()->where('code', 'OPEN-UNIT-01')->firstOrFail();
        $tenant = TenantProfile::query()->with('user')->firstOrFail();
        $lease = Lease::query()->with('installments')->where('code', 'OPEN-LEASE-01')->firstOrFail();
        $payment = Payment::query()->with('allocations')->where('reference', 'OPEN-PAY-01')->firstOrFail();

        $this->assertSame($building->id, $floor->parent_id);
        $this->assertSame($floor->id, $unit->parent_id);
        $this->assertSame('occupied', $unit->fresh()->occupancy_status);
        $this->assertSame('tenant.opening@example.com', $tenant->user?->email);
        $this->assertTrue((bool) $tenant->user?->force_password_reset);
        $this->assertSame($unit->id, $lease->leaseable_id);
        $this->assertGreaterThan(1, $lease->installments->count());
        $this->assertSame(1000.0, (float) $lease->installments->sum('amount_paid'));
        $this->assertNotEmpty($payment->allocations);
        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'opening_data',
            'event' => 'opening_data_imported',
            'subject_id' => $portfolio->id,
        ]);
        $this->assertFalse(Storage::disk('local')->exists(
            "opening-data/previews/{$owner->id}/{$token}.json",
        ));
    }

    public function test_preview_reports_cross_reference_errors_without_writing_records(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $workbook = $this->validWorkbook([
            'Assets' => [
                ['code' => 'DUPLICATE', 'parent_code' => 'MISSING'],
                ['code' => 'DUPLICATE', 'parent_code' => 'DUPLICATE'],
            ],
            'Leases' => [
                ['asset_code' => 'UNKNOWN', 'tenant_email' => 'missing@example.com'],
            ],
        ]);
        $token = $this->previewToken($owner, $portfolio->id, $workbook);

        $this->actingAs($owner)
            ->get(route('opening-data.index', ['preview' => $token]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('openingData.preview.ready', false)
                ->where('openingData.preview.issue_count', fn (int $count): bool => $count >= 4)
            );

        $this->assertDatabaseCount('assets', 0);
        $this->assertDatabaseCount('tenant_profiles', 0);
        $this->assertDatabaseCount('leases', 0);
        $this->assertDatabaseCount('payments', 0);

        $this->actingAs($owner)
            ->post(route('opening-data.store'), [
                'preview_token' => $token,
                'confirmed' => true,
            ])
            ->assertSessionHasErrors('preview_token');
    }

    public function test_owner_cannot_import_into_another_portfolio(): void
    {
        $ownPortfolio = $this->createPortfolio();
        $foreignPortfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $ownPortfolio);

        $this->actingAs($owner)
            ->post(route('opening-data.preview'), [
                'portfolio_id' => $foreignPortfolio->id,
                'file' => $this->validWorkbook(),
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('assets', 0);
    }

    public function test_commit_rolls_back_every_model_when_a_domain_action_fails(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $portfolio->update(['owner_user_id' => $owner->id]);
        $token = $this->previewToken($owner, $portfolio->id, $this->validWorkbook());

        DB::statement(<<<'SQL'
CREATE TRIGGER fail_opening_payment
BEFORE INSERT ON payments
BEGIN
    SELECT RAISE(FAIL, 'forced payment failure');
END
SQL);

        try {
            $this->withoutExceptionHandling()
                ->actingAs($owner)
                ->post(route('opening-data.store'), [
                    'preview_token' => $token,
                    'confirmed' => true,
                ]);
            $this->fail('The forced import failure was not raised.');
        } catch (QueryException $exception) {
            $this->assertStringContainsString(
                'forced payment failure',
                $exception->getMessage(),
            );
        } finally {
            DB::statement('DROP TRIGGER IF EXISTS fail_opening_payment');
        }

        $this->assertDatabaseCount('assets', 0);
        $this->assertDatabaseCount('tenant_profiles', 0);
        $this->assertDatabaseCount('leases', 0);
        $this->assertDatabaseCount('lease_installments', 0);
        $this->assertDatabaseCount('payments', 0);
        $this->assertTrue(Storage::disk('local')->exists(
            "opening-data/previews/{$owner->id}/{$token}.json",
        ));
    }

    private function previewToken(
        User $actor,
        int $portfolioId,
        UploadedFile $workbook,
    ): string {
        $response = $this->actingAs($actor)
            ->post(route('opening-data.preview'), [
                'portfolio_id' => $portfolioId,
                'file' => $workbook,
            ])
            ->assertRedirect();
        $location = (string) $response->headers->get('location');
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);
        $token = $query['preview'] ?? null;
        $this->assertIsString($token);
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9]{48}$/', $token);

        return $token;
    }

    /**
     * @param  array<string, array<int, array<string, mixed>>>  $overrides
     */
    private function validWorkbook(array $overrides = []): UploadedFile
    {
        $records = $this->records();

        foreach ($overrides as $sheet => $rows) {
            foreach ($rows as $index => $override) {
                $base = $records[$sheet][$index] ?? $records[$sheet][0] ?? [];
                $records[$sheet][$index] = [...$base, ...$override];
            }
        }

        $sheets = [['name' => 'Instructions', 'rows' => [['Opening data']]]];

        foreach (OpeningDataWorkbookSchema::SHEETS as $sheet => $headers) {
            $rows = [$headers];

            foreach ($records[$sheet] as $record) {
                $rows[] = array_map(
                    fn (string $header): mixed => $record[$header] ?? null,
                    $headers,
                );
            }

            $sheets[] = ['name' => $sheet, 'rows' => $rows];
        }

        $path = app(XlsxWorkbook::class)->createSheets($sheets);

        return new UploadedFile(
            $path,
            'opening-data.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true,
        );
    }

    /** @return array<string, array<int, array<string, mixed>>> */
    private function records(): array
    {
        return [
            'Assets' => [
                [
                    'code' => 'OPEN-UNIT-01',
                    'parent_code' => 'OPEN-FLR-01',
                    'asset_type' => 'unit',
                    'usage_type' => 'residential',
                    'title_en' => 'Opening Unit 1',
                    'title_ar' => 'الوحدة الافتتاحية 1',
                    'status' => 'active',
                    'occupancy_status' => 'vacant',
                    'rentable' => 1,
                    'valuation_amount' => 350000,
                    'currency' => 'SAR',
                    'area' => 95,
                    'unit_label' => '101',
                ],
                [
                    'code' => 'OPEN-FLR-01',
                    'parent_code' => 'OPEN-BLDG-01',
                    'asset_type' => 'floor',
                    'usage_type' => 'residential',
                    'title_en' => 'Opening Floor 1',
                    'title_ar' => 'الطابق الافتتاحي 1',
                    'status' => 'active',
                    'occupancy_status' => 'vacant',
                    'rentable' => 0,
                    'valuation_amount' => 0,
                    'currency' => 'SAR',
                    'area' => 450,
                    'level_label' => '1',
                ],
                [
                    'code' => 'OPEN-BLDG-01',
                    'parent_code' => null,
                    'asset_type' => 'building',
                    'usage_type' => 'residential',
                    'title_en' => 'Opening Building',
                    'title_ar' => 'المبنى الافتتاحي',
                    'status' => 'active',
                    'occupancy_status' => 'vacant',
                    'rentable' => 0,
                    'valuation_amount' => 3000000,
                    'currency' => 'SAR',
                    'area' => 800,
                    'address_en' => 'Riyadh',
                    'address_ar' => 'الرياض',
                    'latitude' => 24.7136,
                    'longitude' => 46.6753,
                    'zone_en' => 'Central',
                    'zone_ar' => 'الوسطى',
                ],
            ],
            'Tenants' => [
                [
                    'email' => 'tenant.opening@example.com',
                    'name' => 'Opening Tenant',
                    'phone' => '+966500000001',
                    'preferred_locale' => 'ar',
                    'profile_type' => 'individual',
                    'national_id' => 'OPEN-ID-1',
                    'company_name' => null,
                    'address' => 'Riyadh',
                    'status' => 'active',
                    'notes' => 'Opening tenant record',
                ],
            ],
            'Leases' => [
                [
                    'code' => 'OPEN-LEASE-01',
                    'asset_code' => 'OPEN-UNIT-01',
                    'tenant_email' => 'tenant.opening@example.com',
                    'status' => 'active',
                    'payment_frequency' => 'monthly',
                    'started_at' => '2026-01-01',
                    'ends_at' => '2026-12-31',
                    'signed_at' => '2025-12-20',
                    'rent_amount' => 2000,
                    'deposit_amount' => 500,
                    'tax_amount' => 0,
                    'discount_amount' => 0,
                    'currency' => 'SAR',
                    'billing_day' => 1,
                    'renewal_notice_days' => 60,
                    'terms_en' => 'Opening lease terms.',
                    'terms_ar' => 'بنود العقد الافتتاحي.',
                    'notes' => null,
                ],
            ],
            'Payments' => [
                [
                    'lease_code' => 'OPEN-LEASE-01',
                    'reference' => 'OPEN-PAY-01',
                    'received_on' => '2026-01-01',
                    'amount' => 1000,
                    'method' => 'bank_transfer',
                    'type' => 'rent',
                    'notes' => 'Opening balance receipt',
                ],
            ],
        ];
    }
}
