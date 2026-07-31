<?php

namespace Tests\Feature;

use App\Models\MaintenanceRequest;
use App\Models\Portfolio;
use App\Models\TenantProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MaintenanceResolutionWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_management_must_record_a_resolution_summary_before_closing(): void
    {
        [$portfolio, $owner, $tenantUser, $tenant, $request] = $this->context();

        $this->actingAs($owner)
            ->from(route('maintenance-requests.edit', $request))
            ->put(route('maintenance-requests.update', $request), [
                'priority' => 'medium',
                'status' => 'resolved',
            ])
            ->assertRedirect(route('maintenance-requests.edit', $request))
            ->assertSessionHasErrors('resolution_summary');

        $summary = 'Replaced the failed valve and verified the line under pressure.';
        $this->actingAs($owner)
            ->put(route('maintenance-requests.update', $request), [
                'priority' => 'medium',
                'status' => 'resolved',
                'resolution_summary' => $summary,
            ])
            ->assertRedirect(route('maintenance-requests.show', $request));

        $request->refresh();
        $this->assertSame('resolved', $request->status);
        $this->assertSame($owner->id, $request->resolved_by_user_id);
        $this->assertNotNull($request->resolved_at);
        $this->assertDatabaseHas('maintenance_updates', [
            'maintenance_request_id' => $request->id,
            'status_from' => 'open',
            'status_to' => 'resolved',
            'is_public_comment' => true,
            'comment' => $summary,
        ]);

        $this->actingAs($tenantUser)
            ->get(route('maintenance-requests.show', $request))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('detailPage.progress.completed', 4)
                ->where('detailPage.progress.total', 5)
                ->where('detailPage.progress.steps.4.state', 'current')
                ->where('detailPage.workflow.title', 'Tenant sign-off is pending')
                ->where('detailPage.workflow.actions.0.label', 'Review resolution'));

        $this->assertSame($portfolio->id, $tenant->portfolio_id);
    }

    public function test_tenant_can_confirm_the_resolution_and_download_the_pdf_report(): void
    {
        [, $owner, $tenantUser, , $request] = $this->context([
            'status' => 'resolved',
            'resolution_summary' => 'Cleaned the drain and completed a flow test.',
            'resolved_at' => now(),
        ]);
        $request->update(['resolved_by_user_id' => $owner->id]);

        $this->actingAs($tenantUser)
            ->get(route('maintenance-requests.resolution-response.create', $request))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/resource-form')
                ->where('formPage.title', "Review resolution for request #{$request->id}")
                ->where('formPage.fields.0.name', 'outcome')
                ->where('formPage.fields.0.options.0.value', 'confirmed')
                ->where('formPage.fields.0.options.1.value', 'reopen'));

        $this->actingAs($tenantUser)
            ->post(route('maintenance-requests.resolution-response.store', $request), [
                'outcome' => 'confirmed',
                'note' => 'The drain is working normally now.',
            ])
            ->assertRedirect(route('maintenance-requests.show', $request));

        $request->refresh();
        $this->assertSame('resolved', $request->status);
        $this->assertNotNull($request->tenant_confirmed_at);
        $this->assertSame(
            'The drain is working normally now.',
            $request->tenant_confirmation_note,
        );

        $this->actingAs($tenantUser)
            ->get(route('maintenance-requests.show', $request))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('detailPage.progress.completed', 5)
                ->where('detailPage.progress.steps.4.state', 'complete')
                ->where('detailPage.workflow.title', 'Service is complete and confirmed'));

        $report = $this->actingAs($tenantUser)
            ->get(route('maintenance-requests.service-report', $request));

        $report->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertSame('%PDF-', substr($report->streamedContent(), 0, 5));
    }

    public function test_tenant_can_reopen_an_unresolved_result_with_an_accountable_reason(): void
    {
        [, , $tenantUser, , $request] = $this->context([
            'status' => 'resolved',
            'resolution_summary' => 'Adjusted the air conditioner.',
            'resolved_at' => now(),
        ]);

        $this->actingAs($tenantUser)
            ->from(route('maintenance-requests.resolution-response.create', $request))
            ->post(route('maintenance-requests.resolution-response.store', $request), [
                'outcome' => 'reopen',
                'note' => '',
            ])
            ->assertSessionHasErrors('note');

        $this->actingAs($tenantUser)
            ->post(route('maintenance-requests.resolution-response.store', $request), [
                'outcome' => 'reopen',
                'note' => 'Cooling stopped again after two hours.',
            ])
            ->assertRedirect(route('maintenance-requests.show', $request));

        $request->refresh();
        $this->assertSame('open', $request->status);
        $this->assertNull($request->resolved_at);
        $this->assertNull($request->tenant_confirmed_at);
        $this->assertNotNull($request->due_at);
        $this->assertSame(
            'Cooling stopped again after two hours.',
            $request->tenant_confirmation_note,
        );
        $this->assertDatabaseHas('maintenance_updates', [
            'maintenance_request_id' => $request->id,
            'status_from' => 'resolved',
            'status_to' => 'open',
            'is_public_comment' => true,
        ]);
    }

    public function test_resolution_response_and_report_never_cross_tenant_boundaries(): void
    {
        [, , , , $request] = $this->context([
            'status' => 'resolved',
            'resolution_summary' => 'Repair complete.',
            'resolved_at' => now(),
        ]);
        $foreignPortfolio = $this->createPortfolio();
        $foreignTenant = $this->createUserWithRole('tenant', $foreignPortfolio);
        $this->createTenantProfile($foreignPortfolio, $foreignTenant);

        $this->actingAs($foreignTenant)
            ->get(route('maintenance-requests.resolution-response.create', $request))
            ->assertForbidden();
        $this->actingAs($foreignTenant)
            ->post(route('maintenance-requests.resolution-response.store', $request), [
                'outcome' => 'confirmed',
            ])
            ->assertForbidden();
        $this->actingAs($foreignTenant)
            ->get(route('maintenance-requests.service-report', $request))
            ->assertForbidden();
    }

    public function test_unresolved_request_cannot_generate_a_completion_report(): void
    {
        [, $owner, , , $request] = $this->context();

        $this->actingAs($owner)
            ->get(route('maintenance-requests.service-report', $request))
            ->assertStatus(409);
    }

    /**
     * @param  array<string, mixed>  $requestAttributes
     * @return array{Portfolio,User,User,TenantProfile,MaintenanceRequest}
     */
    private function context(array $requestAttributes = []): array
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenantUser = $this->createUserWithRole('tenant', $portfolio);
        $tenant = $this->createTenantProfile($portfolio, $tenantUser);
        $asset = $this->createAsset($portfolio, [
            'title_en' => 'Closeout Unit',
            'title_ar' => 'وحدة إغلاق الصيانة',
        ]);
        $lease = $this->createLease($portfolio, $tenant, $asset, $owner);
        $request = MaintenanceRequest::query()->create([
            'portfolio_id' => $portfolio->id,
            'asset_id' => $asset->id,
            'lease_id' => $lease->id,
            'tenant_profile_id' => $tenant->id,
            'submitted_by_user_id' => $tenantUser->id,
            'category' => 'plumbing',
            'priority' => 'medium',
            'status' => 'open',
            'title' => 'Drain leak',
            'description' => 'Water leaks below the sink.',
            'requested_at' => now()->subDay(),
            'due_at' => now()->addDays(3),
            ...$requestAttributes,
        ]);

        return [$portfolio, $owner, $tenantUser, $tenant, $request];
    }
}
