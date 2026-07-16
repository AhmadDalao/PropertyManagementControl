<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class AuditLogManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_only_see_and_export_their_portfolio_activity(): void
    {
        $portfolio = $this->createPortfolio();
        $foreignPortfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $foreignOwner = $this->createUserWithRole('owner', $foreignPortfolio);
        $asset = $this->createAsset($portfolio, ['title_en' => 'Own Audit Unit']);
        $foreignAsset = $this->createAsset($foreignPortfolio, ['title_en' => 'Foreign Audit Unit']);

        Activity::query()->delete();

        Activity::query()->create([
            'log_name' => 'default',
            'description' => 'Own asset updated',
            'subject_type' => 'asset',
            'subject_id' => $asset->id,
            'event' => 'updated',
            'causer_type' => 'user',
            'causer_id' => $owner->id,
            'attribute_changes' => [
                'attributes' => ['title_en' => 'Own Audit Unit'],
                'old' => ['title_en' => 'Old Unit'],
            ],
        ]);
        Activity::query()->create([
            'log_name' => 'default',
            'description' => 'Foreign asset updated',
            'subject_type' => Asset::class,
            'subject_id' => $foreignAsset->id,
            'event' => 'updated',
            'causer_type' => User::class,
            'causer_id' => $foreignOwner->id,
            'attribute_changes' => [
                'attributes' => ['title_en' => 'Foreign Audit Unit'],
                'old' => ['title_en' => 'Old Foreign Unit'],
            ],
        ]);

        $this->actingAs($owner)
            ->get(route('audit-logs.index'))
            ->assertOk()
            ->assertSee('Own Audit Unit')
            ->assertDontSee('Foreign Audit Unit');

        $export = $this->actingAs($owner)
            ->get(route('audit-logs.export'))
            ->assertOk();

        $sheetXml = $this->xlsxWorksheetXml($export);

        $this->assertStringContainsString('Own Audit Unit', $sheetXml);
        $this->assertStringNotContainsString('Foreign Audit Unit', $sheetXml);
    }

    public function test_superadmin_can_filter_audit_logs_by_portfolio(): void
    {
        $portfolio = $this->createPortfolio();
        $foreignPortfolio = $this->createPortfolio();
        $superadmin = $this->createUserWithRole('superadmin');
        $asset = $this->createAsset($portfolio, ['title_en' => 'Selected Portfolio Asset']);
        $foreignAsset = $this->createAsset($foreignPortfolio, ['title_en' => 'Other Portfolio Asset']);

        Activity::query()->delete();

        Activity::query()->create([
            'description' => 'Selected activity',
            'subject_type' => 'asset',
            'subject_id' => $asset->id,
            'event' => 'updated',
        ]);
        Activity::query()->create([
            'description' => 'Other activity',
            'subject_type' => 'asset',
            'subject_id' => $foreignAsset->id,
            'event' => 'updated',
        ]);

        $this->actingAs($superadmin)
            ->get(route('audit-logs.index', ['portfolio_id' => $portfolio->id]))
            ->assertOk()
            ->assertSee('Selected Portfolio Asset')
            ->assertDontSee('Other Portfolio Asset');
    }

    public function test_tenant_cannot_access_audit_logs(): void
    {
        $portfolio = $this->createPortfolio();
        $tenant = $this->createUserWithRole('tenant', $portfolio);

        $this->actingAs($tenant)
            ->get(route('audit-logs.index'))
            ->assertForbidden();

        $this->actingAs($tenant)
            ->get(route('audit-logs.export'))
            ->assertForbidden();
    }

    public function test_sensitive_user_fields_are_not_logged(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);

        Activity::query()->delete();

        $this->actingAs($owner);
        $owner->update([
            'name' => 'Owner Updated',
            'password' => 'new-local-password',
            'remember_token' => 'secret-token',
        ]);

        $activity = Activity::query()->latest()->firstOrFail();
        $attributes = $activity->attribute_changes?->get('attributes') ?? [];

        $this->assertArrayHasKey('name', $attributes);
        $this->assertArrayNotHasKey('password', $attributes);
        $this->assertArrayNotHasKey('remember_token', $attributes);
    }
}
