<?php

namespace Tests\Feature;

use App\Models\AssetStakeholder;
use App\Models\ExpenseEntry;
use App\Models\MaintenanceRequest;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;
use ZipArchive;

class PropertyOperatingReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_owner_opens_a_dedicated_property_report_with_scoped_structure_and_operations(): void
    {
        $portfolio = $this->createPortfolio([
            'name_en' => 'North Portfolio',
            'name_ar' => 'محفظة الشمال',
        ]);
        $owner = $this->createUserWithRole('owner', $portfolio, ['name' => 'North Owner']);
        $manager = $this->createUserWithRole('property_manager', $portfolio, ['name' => 'North Manager']);
        $property = $this->createAsset($portfolio, [
            'parent_id' => null,
            'asset_type' => 'building',
            'title_en' => 'North Tower',
            'title_ar' => 'برج الشمال',
            'code' => 'NORTH-01',
            'rentable' => false,
            'valuation_amount' => 5000000,
        ]);
        $floor = $this->createAsset($portfolio, [
            'parent_id' => $property->id,
            'asset_type' => 'floor',
            'rentable' => false,
        ]);
        $unit = $this->createAsset($portfolio, [
            'parent_id' => $floor->id,
            'asset_type' => 'unit',
            'title_en' => 'North Unit',
            'occupancy_status' => 'occupied',
        ]);
        $vacantUnit = $this->createAsset($portfolio, [
            'parent_id' => $floor->id,
            'asset_type' => 'unit',
            'occupancy_status' => 'vacant',
        ]);
        $otherProperty = $this->createAsset($portfolio, [
            'parent_id' => null,
            'asset_type' => 'building',
            'rentable' => false,
        ]);
        $otherUnit = $this->createAsset($portfolio, [
            'parent_id' => $otherProperty->id,
            'asset_type' => 'unit',
            'occupancy_status' => 'occupied',
        ]);

        foreach ([[$owner, 'owner'], [$manager, 'manager']] as [$user, $relationship]) {
            AssetStakeholder::query()->create([
                'asset_id' => $property->id,
                'portfolio_id' => $portfolio->id,
                'user_id' => $user->id,
                'relationship_type' => $relationship,
                'is_primary' => true,
            ]);
        }

        $tenant = $this->createTenantProfile(
            $portfolio,
            $this->createUserWithRole('tenant', $portfolio, ['name' => 'North Tenant']),
        );
        $lease = $this->createLease($portfolio, $tenant, $unit, $manager);
        $otherTenant = $this->createTenantProfile(
            $portfolio,
            $this->createUserWithRole('tenant', $portfolio),
        );
        $otherLease = $this->createLease($portfolio, $otherTenant, $otherUnit, $manager);

        Payment::query()->create([
            'portfolio_id' => $portfolio->id,
            'lease_id' => $lease->id,
            'tenant_profile_id' => $tenant->id,
            'recorded_by_user_id' => $manager->id,
            'reference' => 'NORTH-PAY',
            'type' => 'rent',
            'method' => 'bank_transfer',
            'status' => 'posted',
            'received_on' => now()->toDateString(),
            'amount' => 1800,
            'currency' => 'SAR',
        ]);
        Payment::query()->create([
            'portfolio_id' => $portfolio->id,
            'lease_id' => $otherLease->id,
            'tenant_profile_id' => $otherTenant->id,
            'recorded_by_user_id' => $manager->id,
            'reference' => 'OTHER-PAY',
            'type' => 'rent',
            'method' => 'cash',
            'status' => 'posted',
            'received_on' => now()->toDateString(),
            'amount' => 9000,
            'currency' => 'SAR',
        ]);
        ExpenseEntry::query()->create([
            'portfolio_id' => $portfolio->id,
            'asset_id' => $unit->id,
            'created_by_user_id' => $manager->id,
            'category' => 'maintenance',
            'title' => 'North repair',
            'incurred_on' => now()->toDateString(),
            'amount' => 300,
            'currency' => 'SAR',
            'status' => 'posted',
        ]);
        MaintenanceRequest::query()->create([
            'portfolio_id' => $portfolio->id,
            'asset_id' => $unit->id,
            'tenant_profile_id' => $tenant->id,
            'submitted_by_user_id' => $tenant->user_id,
            'category' => 'plumbing',
            'priority' => 'high',
            'status' => 'open',
            'title' => 'North leak',
            'description' => 'Kitchen leak',
            'requested_at' => now(),
        ]);

        $this->actingAs($owner)
            ->get(route('reports.properties.show', [
                'asset' => $property,
                'date_from' => now()->startOfYear()->toDateString(),
                'date_to' => now()->toDateString(),
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/reports/property')
                ->where('property.id', $property->id)
                ->where('property.title_en', 'North Tower')
                ->where('property.owner.name', 'North Owner')
                ->where('property.manager.name', 'North Manager')
                ->where('property.structure.records', 4)
                ->where('property.structure.floors', 1)
                ->where('property.structure.units', 2)
                ->where('property.structure.rentable', 2)
                ->where('property.structure.occupied', 1)
                ->where('property.structure.vacant', 1)
                ->where('property.structure.active_tenants', 1)
                ->where('summary.revenue', fn (int|float $value) => (float) $value === 1800.0)
                ->where('summary.expenses', fn (int|float $value) => (float) $value === 300.0)
                ->where('summary.openRequests', 1)
                ->where('recentPayments.0.reference', 'NORTH-PAY')
                ->where('maintenanceBacklog.0.title', 'North leak')
                ->where('property.links.action_center', route('action-center.index', [
                    'property_id' => $property->id,
                ], false))
                ->where('property.downloads.xlsx', fn (string $href): bool => str_contains(
                    $href,
                    "/reports/properties/{$property->id}/operating-report.xlsx",
                )));

        $query = [
            'asset' => $property,
            'date_from' => now()->startOfYear()->toDateString(),
            'date_to' => now()->toDateString(),
        ];
        $pdf = $this->actingAs($owner)
            ->get(route('reports.properties.pdf', $query))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
        $this->assertSame('%PDF-', substr($pdf->streamedContent(), 0, 5));
        $this->assertStringContainsString(
            'property-operating-report-north-01-',
            (string) $pdf->headers->get('content-disposition'),
        );

        $word = $this->actingAs($owner)
            ->get(route('reports.properties.word', $query))
            ->assertOk()
            ->assertHeader(
                'content-type',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            );
        $wordPath = tempnam(sys_get_temp_dir(), 'property-operating-word-');
        $this->assertNotFalse($wordPath);
        file_put_contents($wordPath, $word->streamedContent());
        $wordZip = new ZipArchive;
        $this->assertTrue($wordZip->open($wordPath));
        $documentXml = (string) $wordZip->getFromName('word/document.xml');
        $wordZip->close();
        @unlink($wordPath);
        $this->assertStringContainsString('Property Operating Report', $documentXml);
        $this->assertStringContainsString('تقرير تشغيل العقار', $documentXml);
        $this->assertStringContainsString('North Tower', $documentXml);
        $this->assertStringContainsString('NORTH-PAY', $documentXml);
        $this->assertStringNotContainsString('OTHER-PAY', $documentXml);

        $workbook = $this->actingAs($owner)
            ->get(route('reports.properties.workbook', $query))
            ->assertOk()
            ->assertHeader(
                'content-type',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            );
        $workbookPath = $workbook->baseResponse->getFile()->getPathname();
        $this->assertSame('PK', substr((string) file_get_contents($workbookPath), 0, 2));
        $workbookZip = new ZipArchive;
        $this->assertTrue($workbookZip->open($workbookPath));
        $workbookXml = (string) $workbookZip->getFromName('xl/workbook.xml');
        $allSheets = collect(range(1, 5))
            ->map(fn (int $index): string => (string) $workbookZip->getFromName(
                "xl/worksheets/sheet{$index}.xml",
            ))
            ->join("\n");
        $workbookZip->close();

        foreach (['Overview', 'Collections', 'Costs', 'Maintenance', 'Activity'] as $sheet) {
            $this->assertStringContainsString('name="'.$sheet.'"', $workbookXml);
        }
        $this->assertStringContainsString('North Tower', $allSheets);
        $this->assertStringContainsString('NORTH-PAY', $allSheets);
        $this->assertStringContainsString('North repair', $allSheets);
        $this->assertStringContainsString('North leak', $allSheets);
        $this->assertStringNotContainsString('OTHER-PAY', $allSheets);
    }

    public function test_property_report_rejects_tenants_children_and_unassigned_properties(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $manager = $this->createUserWithRole('property_manager', $portfolio);
        $tenant = $this->createUserWithRole('tenant', $portfolio);
        $property = $this->createAsset($portfolio, [
            'parent_id' => null,
            'asset_type' => 'building',
            'rentable' => false,
        ]);
        $child = $this->createAsset($portfolio, ['parent_id' => $property->id]);
        $otherProperty = $this->createAsset($portfolio, [
            'parent_id' => null,
            'asset_type' => 'building',
            'rentable' => false,
        ]);
        $rootFloor = $this->createAsset($portfolio, [
            'parent_id' => null,
            'asset_type' => 'floor',
            'rentable' => false,
        ]);
        $this->assignManagerToAsset($manager, $property);

        $this->actingAs($manager)
            ->get(route('reports.properties.show', $property))
            ->assertOk();
        $this->actingAs($manager)
            ->get(route('reports.properties.workbook', $property))
            ->assertOk();
        $this->actingAs($manager)
            ->get(route('reports.properties.show', $otherProperty))
            ->assertForbidden();
        $this->actingAs($manager)
            ->get(route('reports.properties.pdf', $otherProperty))
            ->assertForbidden();
        $this->actingAs($owner)
            ->get(route('reports.properties.show', $child))
            ->assertNotFound();
        $this->actingAs($owner)
            ->get(route('reports.properties.show', $rootFloor))
            ->assertNotFound();
        $this->actingAs($tenant)
            ->get(route('reports.properties.show', $property))
            ->assertForbidden();
        $this->actingAs($tenant)
            ->get(route('reports.properties.word', $property))
            ->assertForbidden();
    }

    public function test_property_exports_include_the_complete_scoped_period_not_the_browser_preview(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $manager = $this->createUserWithRole('property_manager', $portfolio);
        $property = $this->createAsset($portfolio, [
            'parent_id' => null,
            'asset_type' => 'building',
            'title_en' => 'Complete Tower',
            'code' => 'COMPLETE-01',
            'rentable' => false,
        ]);
        $unit = $this->createAsset($portfolio, [
            'parent_id' => $property->id,
            'asset_type' => 'unit',
        ]);
        $tenant = $this->createTenantProfile(
            $portfolio,
            $this->createUserWithRole('tenant', $portfolio),
        );
        $lease = $this->createLease($portfolio, $tenant, $unit, $manager);

        foreach (range(1, 12) as $sequence) {
            Payment::query()->create([
                'portfolio_id' => $portfolio->id,
                'lease_id' => $lease->id,
                'tenant_profile_id' => $tenant->id,
                'recorded_by_user_id' => $manager->id,
                'reference' => sprintf('COMPLETE-PAY-%02d', $sequence),
                'type' => 'rent',
                'method' => 'bank_transfer',
                'status' => 'posted',
                'received_on' => today()->subDays($sequence),
                'amount' => 100 + $sequence,
                'currency' => 'SAR',
            ]);
        }

        $query = [
            'asset' => $property,
            'date_from' => today()->subMonth()->toDateString(),
            'date_to' => today()->toDateString(),
        ];

        $this->actingAs($owner)
            ->get(route('reports.properties.show', $query))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('recentPayments', 8));

        $word = $this->actingAs($owner)
            ->get(route('reports.properties.word', $query))
            ->assertOk();
        $wordPath = tempnam(sys_get_temp_dir(), 'property-full-word-');
        $this->assertNotFalse($wordPath);
        file_put_contents($wordPath, $word->streamedContent());
        $wordZip = new ZipArchive;
        $this->assertTrue($wordZip->open($wordPath));
        $documentXml = (string) $wordZip->getFromName('word/document.xml');
        $wordZip->close();
        @unlink($wordPath);

        $this->assertStringContainsString('COMPLETE-PAY-01', $documentXml);
        $this->assertStringContainsString('COMPLETE-PAY-12', $documentXml);

        $workbook = $this->actingAs($owner)
            ->get(route('reports.properties.workbook', $query))
            ->assertOk();
        $workbookPath = $workbook->baseResponse->getFile()->getPathname();
        $workbookZip = new ZipArchive;
        $this->assertTrue($workbookZip->open($workbookPath));
        $collections = (string) $workbookZip->getFromName('xl/worksheets/sheet2.xml');
        $workbookZip->close();

        $this->assertStringContainsString('COMPLETE-PAY-01', $collections);
        $this->assertStringContainsString('COMPLETE-PAY-12', $collections);
    }
}
