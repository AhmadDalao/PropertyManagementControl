<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Document;
use App\Models\Lease;
use App\Models\LeaseMoveOut;
use App\Models\Portfolio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

final class LeaseMoveOutWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->travelTo('2026-07-25 10:00:00');
    }

    public function test_owner_can_plan_and_update_an_audited_move_out(): void
    {
        [$portfolio, $owner, $property, $asset, $lease] = $this->leaseFixture();

        $this->actingAs($owner)
            ->get(route('leases.move-out.edit', $lease))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/resource-form')
                ->where('formPage.action', route('leases.move-out.update', $lease))
                ->where('formPage.method', 'put')
                ->where('formPage.initialValues.reason', 'tenant_notice')
                ->where('formPage.initialValues.deposit_disposition', 'not_applicable'));

        $this->actingAs($owner)
            ->put(route('leases.move-out.update', $lease), [
                'move_out_date' => today()->addDays(14)->toDateString(),
                'reason' => 'owner_notice',
                'deposit_disposition' => 'not_applicable',
                'deposit_deduction_amount' => 0,
                'keys_returned' => false,
                'notes' => 'Owner approved the handover date.',
            ])
            ->assertRedirect(route('leases.show', $lease));

        $moveOut = LeaseMoveOut::query()->where('lease_id', $lease->id)->firstOrFail();

        $this->assertSame($portfolio->id, $moveOut->portfolio_id);
        $this->assertSame($owner->id, $moveOut->initiated_by_user_id);
        $this->assertSame('planned', $moveOut->status);
        $this->assertDatabaseHas('activity_log', [
            'subject_type' => 'lease_move_out',
            'subject_id' => $moveOut->id,
            'event' => 'created',
        ]);
        $activityId = Activity::query()
            ->where('subject_type', 'lease_move_out')
            ->where('subject_id', $moveOut->id)
            ->value('id');

        $this->actingAs($owner)
            ->get(route('leases.show', $lease))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('detailPage.timeline', fn ($timeline): bool => collect($timeline)
                    ->contains('id', $activityId)));
        $this->assertNotNull($property);
        $this->assertNotNull($asset);
    }

    public function test_active_lease_cannot_bypass_the_move_out_workflow(): void
    {
        [, $owner, , , $lease] = $this->leaseFixture();

        $this->actingAs($owner)
            ->delete(route('leases.destroy', $lease))
            ->assertSessionHasErrors('lease');

        $this->assertSame('active', $lease->fresh()->status);
        $this->assertDatabaseMissing('lease_move_outs', ['lease_id' => $lease->id]);
    }

    public function test_completion_requires_handover_evidence_then_releases_the_asset_without_erasing_debt(): void
    {
        [$portfolio, $owner, , $asset, $lease] = $this->leaseFixture();
        $asset->update(['occupancy_status' => 'occupied']);
        $outstanding = (float) $lease->installments()->sum('amount_due');

        $this->plan($owner, $lease, [
            'move_out_date' => today()->toDateString(),
            'keys_returned' => true,
        ]);

        $this->actingAs($owner)
            ->post(route('leases.move-out.complete', $lease))
            ->assertSessionHasErrors([
                'termination_notice',
                'move_out_inspection',
            ]);

        $this->document($portfolio, $owner, $lease, 'termination_notice');
        $this->document($portfolio, $owner, $lease, 'move_out_inspection');

        $this->actingAs($owner)
            ->post(route('leases.move-out.complete', $lease))
            ->assertRedirect(route('leases.show', $lease));

        $moveOut = $lease->moveOut()->firstOrFail();

        $this->assertSame('completed', $moveOut->status);
        $this->assertSame($owner->id, $moveOut->completed_by_user_id);
        $this->assertSame($outstanding, (float) $moveOut->balance_at_completion);
        $this->assertSame('terminated', $lease->fresh()->status);
        $this->assertSame('vacant', $asset->fresh()->occupancy_status);
        $this->assertSame($outstanding, (float) $lease->installments()->sum('amount_due'));
        $this->assertSame(0.0, (float) $lease->installments()->sum('amount_paid'));

        $this->actingAs($owner)
            ->get(route('leases.show', $lease))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('detailPage.progress.title', trans('app.lease_move_outs.progress_title'))
                ->where('detailPage.workflow.actions', fn ($actions): bool => collect($actions)
                    ->contains('href', route('payments.create', ['lease_id' => $lease->id]))));
    }

    public function test_future_date_and_unfinished_handover_block_completion(): void
    {
        [$portfolio, $owner, , , $lease] = $this->leaseFixture();
        $this->plan($owner, $lease, [
            'move_out_date' => today()->addDay()->toDateString(),
            'keys_returned' => false,
        ]);
        $this->document($portfolio, $owner, $lease, 'termination_notice');
        $this->document($portfolio, $owner, $lease, 'move_out_inspection');

        $this->actingAs($owner)
            ->post(route('leases.move-out.complete', $lease))
            ->assertSessionHasErrors([
                'move_out_date',
                'keys_returned',
            ]);

        $this->assertSame('planned', $lease->moveOut()->firstOrFail()->status);
        $this->assertSame('active', $lease->fresh()->status);
    }

    public function test_deposit_decisions_must_match_the_contract_amount(): void
    {
        [, $owner, , , $lease] = $this->leaseFixture();
        $lease->update(['deposit_amount' => 1000]);

        $this->actingAs($owner)
            ->put(route('leases.move-out.update', $lease), [
                'move_out_date' => today()->addDays(10)->toDateString(),
                'reason' => 'tenant_notice',
                'deposit_disposition' => 'not_applicable',
                'deposit_deduction_amount' => 0,
                'keys_returned' => false,
            ])
            ->assertSessionHasErrors('deposit_disposition');

        $this->actingAs($owner)
            ->put(route('leases.move-out.update', $lease), [
                'move_out_date' => today()->addDays(10)->toDateString(),
                'reason' => 'tenant_notice',
                'deposit_disposition' => 'retained',
                'deposit_deduction_amount' => 500,
                'keys_returned' => false,
            ])
            ->assertSessionHasErrors('deposit_disposition');

        $this->actingAs($owner)
            ->put(route('leases.move-out.update', $lease), [
                'move_out_date' => today()->addDays(10)->toDateString(),
                'reason' => 'tenant_notice',
                'deposit_disposition' => 'refund_partial',
                'deposit_deduction_amount' => 250,
                'keys_returned' => false,
            ])
            ->assertRedirect(route('leases.show', $lease));

        $this->assertSame('refund_partial', $lease->moveOut()->firstOrFail()->deposit_disposition);
        $this->assertSame(250.0, (float) $lease->moveOut()->firstOrFail()->deposit_deduction_amount);

        $lease->update(['deposit_amount' => 0]);

        $this->actingAs($owner)
            ->post(route('leases.move-out.complete', $lease))
            ->assertSessionHasErrors('deposit_deduction_amount');
    }

    public function test_owner_can_cancel_and_replan_but_cannot_change_a_completed_handover(): void
    {
        [$portfolio, $owner, , , $lease] = $this->leaseFixture();
        $this->plan($owner, $lease);

        $this->actingAs($owner)
            ->delete(route('leases.move-out.destroy', $lease))
            ->assertRedirect(route('leases.show', $lease));
        $this->assertSame('cancelled', $lease->moveOut()->firstOrFail()->status);
        $this->actingAs($owner)
            ->delete(route('leases.move-out.destroy', $lease))
            ->assertSessionHasErrors('move_out');

        $this->plan($owner, $lease, [
            'move_out_date' => today()->toDateString(),
            'keys_returned' => true,
        ]);
        $this->document($portfolio, $owner, $lease, 'termination_notice');
        $this->document($portfolio, $owner, $lease, 'move_out_inspection');
        $this->actingAs($owner)->post(route('leases.move-out.complete', $lease))->assertRedirect();

        $this->actingAs($owner)
            ->delete(route('leases.move-out.destroy', $lease))
            ->assertSessionHasErrors('move_out');
    }

    public function test_move_out_workspace_search_pagination_dashboard_and_xlsx_share_owner_scope(): void
    {
        $portfolio = $this->createPortfolio();
        $foreignPortfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $foreignOwner = $this->createUserWithRole('owner', $foreignPortfolio);
        $property = $this->property($portfolio, 'Owner Move-out Tower');
        $foreignProperty = $this->property($foreignPortfolio, 'Foreign Move-out Tower');

        foreach (range(1, 12) as $sequence) {
            $lease = $this->leaseAtProperty(
                $portfolio,
                $owner,
                $property,
                sprintf('OWNER-MOVE-%02d', $sequence),
            );
            LeaseMoveOut::query()->create([
                'portfolio_id' => $portfolio->id,
                'lease_id' => $lease->id,
                'initiated_by_user_id' => $owner->id,
                'status' => 'planned',
                'move_out_date' => today()->addDays($sequence),
                'reason' => 'tenant_notice',
                'deposit_disposition' => 'not_applicable',
            ]);
        }

        $foreignLease = $this->leaseAtProperty(
            $foreignPortfolio,
            $foreignOwner,
            $foreignProperty,
            'FOREIGN-MOVE-ONLY',
        );
        LeaseMoveOut::query()->create([
            'portfolio_id' => $foreignPortfolio->id,
            'lease_id' => $foreignLease->id,
            'initiated_by_user_id' => $foreignOwner->id,
            'status' => 'planned',
            'move_out_date' => today()->addDays(5),
            'reason' => 'tenant_notice',
            'deposit_disposition' => 'not_applicable',
        ]);

        $filters = [
            'queue' => 'all',
            'horizon' => 'all',
            'property_id' => $property->id,
            'search' => 'OWNER-MOVE',
            'per_page' => 10,
        ];

        $this->actingAs($owner)
            ->get(route('lease-move-outs.index', $filters))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/lease-move-outs/index')
                ->where('moveOuts.total', 12)
                ->has('moveOuts.data', 10)
                ->where('filters.search', 'OWNER-MOVE')
                ->where('filters.property_id', (string) $property->id)
                ->where('moveOuts.data.0.property.id', $property->id));

        $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('moveOutQueue.items.0.code', 'OWNER-MOVE-01')
                ->where('moveOutQueue.attention', 0));

        $export = $this->actingAs($owner)
            ->get(route('exports.resource', [
                'resource' => 'lease-move-outs',
                ...$filters,
                'search' => 'OWNER-MOVE-01',
            ]))
            ->assertOk();
        $worksheet = $this->xlsxWorksheetXml($export);

        $this->assertStringContainsString('OWNER-MOVE-01', $worksheet);
        $this->assertStringNotContainsString('FOREIGN-MOVE-ONLY', $worksheet);
    }

    public function test_manager_assignment_tenant_and_foreign_portfolio_boundaries_are_enforced(): void
    {
        $portfolio = $this->createPortfolio();
        $foreignPortfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $manager = $this->createUserWithRole('property_manager', $portfolio);
        $tenantUser = $this->createUserWithRole('tenant', $portfolio);
        $foreignOwner = $this->createUserWithRole('owner', $foreignPortfolio);
        $assignedProperty = $this->property($portfolio, 'Assigned Tower');
        $unassignedProperty = $this->property($portfolio, 'Unassigned Tower');
        $foreignProperty = $this->property($foreignPortfolio, 'Foreign Tower');
        $this->assignManagerToAsset($manager, $assignedProperty);
        $assignedLease = $this->leaseAtProperty($portfolio, $owner, $assignedProperty, 'ASSIGNED-MOVE');
        $unassignedLease = $this->leaseAtProperty($portfolio, $owner, $unassignedProperty, 'UNASSIGNED-MOVE');
        $foreignLease = $this->leaseAtProperty($foreignPortfolio, $foreignOwner, $foreignProperty, 'FOREIGN-MOVE');

        $this->actingAs($manager)
            ->get(route('leases.move-out.edit', $assignedLease))
            ->assertOk();
        $this->actingAs($manager)
            ->get(route('leases.move-out.edit', $unassignedLease))
            ->assertForbidden();
        $this->actingAs($tenantUser)
            ->get(route('lease-move-outs.index'))
            ->assertForbidden();
        $this->actingAs($owner)
            ->get(route('leases.move-out.edit', $foreignLease))
            ->assertForbidden();
    }

    public function test_arabic_workspace_exposes_rtl_copy_and_localized_property_titles(): void
    {
        [$portfolio, $owner, $property, , $lease] = $this->leaseFixture(
            propertyTitle: 'Arabic Move-out Tower',
            propertyTitleAr: 'برج الإخلاء',
        );
        LeaseMoveOut::query()->create([
            'portfolio_id' => $portfolio->id,
            'lease_id' => $lease->id,
            'initiated_by_user_id' => $owner->id,
            'status' => 'planned',
            'move_out_date' => today(),
            'reason' => 'natural_expiry',
            'deposit_disposition' => 'not_applicable',
        ]);

        $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('lease-move-outs.index', ['queue' => 'all', 'horizon' => 'all']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('app.locale', 'ar')
                ->where('app.direction', 'rtl')
                ->where('app.translations.nav.lease_move_outs', 'إخلاء الوحدات')
                ->where('app.translations.lease_move_outs.title', 'إخلاء الوحدات')
                ->where('moveOuts.data.0.property.id', $property->id)
                ->where('moveOuts.data.0.property.title_ar', 'برج الإخلاء'));
    }

    /**
     * @return array{Portfolio,User,Asset,Asset,Lease}
     */
    private function leaseFixture(
        string $propertyTitle = 'Move-out Tower',
        string $propertyTitleAr = 'برج الإخلاء',
    ): array {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $property = $this->property($portfolio, $propertyTitle, $propertyTitleAr);
        $lease = $this->leaseAtProperty($portfolio, $owner, $property, 'MOVE-OUT-001');
        $asset = $lease->leaseable;
        $this->assertInstanceOf(Asset::class, $asset);

        return [$portfolio, $owner, $property, $asset, $lease];
    }

    private function property(
        Portfolio $portfolio,
        string $title,
        ?string $titleAr = null,
    ): Asset {
        return $this->createAsset($portfolio, [
            'asset_type' => 'building',
            'title_en' => $title,
            'title_ar' => $titleAr ?? "عقار {$title}",
            'rentable' => false,
        ]);
    }

    private function leaseAtProperty(
        Portfolio $portfolio,
        User $owner,
        Asset $property,
        string $code,
    ): Lease {
        $unit = $this->createAsset($portfolio, [
            'parent_id' => $property->id,
            'title_en' => "{$code} Unit",
            'title_ar' => "وحدة {$code}",
        ]);
        $tenant = $this->createTenantProfile(
            $portfolio,
            $this->createUserWithRole('tenant', $portfolio, ['name' => "{$code} Tenant"]),
        );

        return $this->createLease(
            $portfolio,
            $tenant,
            $unit,
            $owner,
            [
                'code' => $code,
                'deposit_amount' => 0,
                'started_at' => today()->subMonths(2),
                'ends_at' => today()->addMonths(10),
            ],
        );
    }

    /** @param array<string, mixed> $overrides */
    private function plan(User $actor, Lease $lease, array $overrides = []): void
    {
        $this->actingAs($actor)
            ->put(route('leases.move-out.update', $lease), [
                'move_out_date' => today()->addDays(10)->toDateString(),
                'reason' => 'tenant_notice',
                'deposit_disposition' => 'not_applicable',
                'deposit_deduction_amount' => 0,
                'keys_returned' => false,
                'notes' => null,
                ...$overrides,
            ])
            ->assertRedirect(route('leases.show', $lease));
    }

    private function document(
        Portfolio $portfolio,
        User $actor,
        Lease $lease,
        string $type,
    ): Document {
        return Document::query()->create([
            'portfolio_id' => $portfolio->id,
            'uploaded_by_user_id' => $actor->id,
            'documentable_type' => $lease->getMorphClass(),
            'documentable_id' => $lease->id,
            'type' => $type,
            'title_en' => "{$type} {$lease->code}",
            'title_ar' => "{$type} {$lease->code}",
            'disk' => 'local',
            'file_path' => "tests/{$lease->code}-{$type}.pdf",
            'original_name' => "{$lease->code}-{$type}.pdf",
            'mime_type' => 'application/pdf',
            'file_size' => 128,
            'is_public' => true,
        ]);
    }
}
