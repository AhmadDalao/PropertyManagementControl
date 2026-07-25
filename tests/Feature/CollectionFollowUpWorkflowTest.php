<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\CollectionFollowUp;
use App\Models\Lease;
use App\Models\LeaseInstallment;
use App\Models\Portfolio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CollectionFollowUpWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->travelTo(now()->setDate(2026, 7, 25)->setTime(10, 0));
    }

    protected function tearDown(): void
    {
        $this->travelBack();

        parent::tearDown();
    }

    public function test_owner_records_a_promise_and_sees_an_append_only_collection_history(): void
    {
        $context = $this->collectionContext();
        $owner = $context['owner'];
        $manager = $context['manager'];
        $installment = $context['installment'];

        $this->actingAs($owner)
            ->get(route('rent-collection.follow-up', $installment))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/rent-collection/follow-up')
                ->where('collection.installment.id', $installment->id)
                ->where('collection.tenant.name', 'Collection Tenant')
                ->where('collection.latest_follow_up.state', 'untracked')
                ->where('collection.latest_follow_up.history_count', 0)
                ->where('collection.can_record', true)
                ->where(
                    'collection.assignee_options',
                    fn ($options): bool => collect($options)->pluck('id')->contains($owner->id)
                        && collect($options)->pluck('id')->contains($manager->id),
                ));

        $this->actingAs($owner)
            ->post(
                route('rent-collection.follow-ups.store', $installment),
                $this->followUpPayload($manager, [
                    'outcome' => 'promise_to_pay',
                    'promised_amount' => 600,
                    'promised_on' => today()->addDays(3)->toDateString(),
                    'next_follow_up_on' => today()->addDays(3)->toDateString(),
                    'note' => 'Tenant promised a bank transfer after salary day.',
                ]),
            )
            ->assertRedirect(route('rent-collection.follow-up', $installment))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('collection_follow_ups', [
            'portfolio_id' => $context['portfolio']->id,
            'lease_id' => $context['lease']->id,
            'lease_installment_id' => $installment->id,
            'recorded_by_user_id' => $owner->id,
            'assigned_to_user_id' => $manager->id,
            'outcome' => 'promise_to_pay',
            'outstanding_amount_at_contact' => 800,
            'promised_amount' => 600,
            'note' => 'Tenant promised a bank transfer after salary day.',
        ]);
        $firstFollowUp = CollectionFollowUp::query()->firstOrFail();
        $this->assertDatabaseHas('activity_log', [
            'event' => 'created',
            'subject_type' => 'collection_follow_up',
            'subject_id' => $firstFollowUp->id,
            'causer_id' => $owner->id,
        ]);

        $this->actingAs($manager)
            ->post(
                route('rent-collection.follow-ups.store', $installment),
                $this->followUpPayload($manager, [
                    'outcome' => 'contacted',
                    'next_follow_up_on' => today()->addDay()->toDateString(),
                    'note' => 'Manager confirmed the bank details by phone.',
                ]),
            )
            ->assertRedirect(route('rent-collection.follow-up', $installment));

        $this->assertSame(2, CollectionFollowUp::query()->count());
        $this->actingAs($owner)
            ->get(route('rent-collection.follow-up', $installment))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('collection.latest_follow_up.state', 'scheduled')
                ->where('collection.latest_follow_up.history_count', 2)
                ->has('collection.history', 2)
                ->where('collection.history.0.note', 'Manager confirmed the bank details by phone.')
                ->where('collection.history.1.note', 'Tenant promised a bank transfer after salary day.'));
    }

    public function test_broken_promises_drive_filters_dashboard_priority_and_xlsx_exports(): void
    {
        $context = $this->collectionContext();
        $installment = $context['installment'];
        $owner = $context['owner'];

        $this->actingAs($owner)
            ->post(
                route('rent-collection.follow-ups.store', $installment),
                $this->followUpPayload($owner, [
                    'outcome' => 'promise_to_pay',
                    'promised_amount' => 500,
                    'promised_on' => today()->addDay()->toDateString(),
                    'next_follow_up_on' => today()->addDay()->toDateString(),
                    'note' => 'Broken promise export marker.',
                ]),
            )
            ->assertRedirect();

        $this->travel(2)->days();

        $this->actingAs($owner)
            ->get(route('rent-collection.index', [
                'status' => 'all',
                'follow_up' => 'broken',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.follow_up', 'broken')
                ->where('installments.total', 1)
                ->where('installments.data.0.id', $installment->id)
                ->where('installments.data.0.follow_up.state', 'broken')
                ->where('collectionInsights.broken_promises_count', 1)
                ->where('collectionInsights.follow_up_due_count', 0));

        $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('collectionQueue.0.id', $installment->id)
                ->where('collectionQueue.0.follow_up_state', 'broken')
                ->where('collectionQueue.0.assigned_to', $owner->name));

        $this->actingAs($owner)
            ->get(route('reports.index', ['tab' => 'collections']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('summary.openCollectionCount', 1)
                ->where('summary.untrackedOverdueCount', 0)
                ->where('summary.followUpDueCount', 0)
                ->where('summary.brokenPromisesCount', 1));

        $export = $this->actingAs($owner)
            ->get(route('exports.resource', [
                'resource' => 'rent-collection',
                'status' => 'all',
                'follow_up' => 'broken',
            ]))
            ->assertOk();
        $worksheet = $this->xlsxWorksheetXml($export);

        $this->assertStringContainsString('Broken promise export marker.', $worksheet);
        $this->assertStringContainsString('Broken promise', $worksheet);

        $reportWorksheet = $this->xlsxWorksheetXml(
            $this->actingAs($owner)->get(route('reports.export'))->assertOk(),
        );
        $this->assertStringContainsString('Broken promises', $reportWorksheet);
    }

    public function test_collection_follow_up_enforces_balance_lease_and_assignee_rules(): void
    {
        $context = $this->collectionContext();
        $owner = $context['owner'];
        $installment = $context['installment'];
        $foreignManager = $this->createUserWithRole('property_manager', $context['portfolio']);

        $this->actingAs($owner)
            ->from(route('rent-collection.follow-up', $installment))
            ->post(
                route('rent-collection.follow-ups.store', $installment),
                $this->followUpPayload($owner, [
                    'outcome' => 'promise_to_pay',
                    'promised_amount' => 801,
                    'promised_on' => today()->addDay()->toDateString(),
                    'note' => 'Invalid amount.',
                ]),
            )
            ->assertRedirect(route('rent-collection.follow-up', $installment))
            ->assertSessionHasErrors('promised_amount');

        $this->actingAs($owner)
            ->from(route('rent-collection.follow-up', $installment))
            ->post(
                route('rent-collection.follow-ups.store', $installment),
                $this->followUpPayload($foreignManager),
            )
            ->assertRedirect(route('rent-collection.follow-up', $installment))
            ->assertSessionHasErrors('assigned_to_user_id');

        $installment->update([
            'amount_paid' => $installment->amount_due,
            'status' => 'paid',
        ]);

        $this->actingAs($owner)
            ->post(
                route('rent-collection.follow-ups.store', $installment),
                $this->followUpPayload($owner),
            )
            ->assertSessionHasErrors('installment');

        $this->assertDatabaseCount('collection_follow_ups', 0);
    }

    public function test_a_fulfilled_partial_promise_is_not_marked_broken_while_balance_remains(): void
    {
        $context = $this->collectionContext();
        $owner = $context['owner'];
        $installment = $context['installment'];

        $this->actingAs($owner)
            ->post(
                route('rent-collection.follow-ups.store', $installment),
                $this->followUpPayload($owner, [
                    'outcome' => 'promise_to_pay',
                    'promised_amount' => 300,
                    'promised_on' => today()->addDay()->toDateString(),
                    'next_follow_up_on' => today()->addDays(10)->toDateString(),
                    'note' => 'Tenant promised a partial amount.',
                ]),
            )
            ->assertRedirect();

        $installment->update(['amount_paid' => 500, 'status' => 'partial']);
        $this->travel(2)->days();

        $this->actingAs($owner)
            ->get(route('rent-collection.follow-up', $installment))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where(
                    'collection.installment.outstanding_amount',
                    fn (int|float $value): bool => (float) $value === 500.0,
                )
                ->where('collection.latest_follow_up.state', 'scheduled'));

        $this->actingAs($owner)
            ->get(route('rent-collection.index', [
                'status' => 'all',
                'follow_up' => 'broken',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('installments.total', 0)
                ->where('collectionInsights.broken_promises_count', 0));

        $this->actingAs($owner)
            ->get(route('rent-collection.index', [
                'status' => 'all',
                'follow_up' => 'scheduled',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('installments.total', 1)
                ->where('installments.data.0.id', $installment->id));
    }

    public function test_tenant_foreign_owner_and_unassigned_manager_cannot_access_internal_follow_ups(): void
    {
        $context = $this->collectionContext();
        $installment = $context['installment'];
        $tenant = $context['tenant'];
        $unassignedManager = $this->createUserWithRole(
            'property_manager',
            $context['portfolio'],
        );
        $foreignPortfolio = $this->createPortfolio();
        $foreignOwner = $this->createUserWithRole('owner', $foreignPortfolio);

        foreach ([$tenant, $unassignedManager, $foreignOwner] as $actor) {
            $this->actingAs($actor)
                ->get(route('rent-collection.follow-up', $installment))
                ->assertForbidden();
            $this->actingAs($actor)
                ->post(
                    route('rent-collection.follow-ups.store', $installment),
                    $this->followUpPayload($context['owner']),
                )
                ->assertForbidden();
        }

        $this->actingAs($context['manager'])
            ->get(route('rent-collection.follow-up', $installment))
            ->assertOk();
    }

    public function test_arabic_follow_up_page_resolves_real_arabic_interface_copy(): void
    {
        $context = $this->collectionContext();

        $this->actingAs($context['owner'])
            ->withSession(['locale' => 'ar'])
            ->get(route('rent-collection.follow-up', $context['installment']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('app.locale', 'ar')
                ->where('app.direction', 'rtl')
                ->where('app.translations.rent_collection.follow_up_title', 'متابعة التحصيل')
                ->where('collection.contact_method_options.0.label', 'مكالمة هاتفية')
                ->where('collection.outcome_options.0.label', 'تم التواصل'));
    }

    /**
     * @return array{
     *     portfolio: Portfolio,
     *     owner: User,
     *     manager: User,
     *     tenant: User,
     *     root: Asset,
     *     lease: Lease,
     *     installment: LeaseInstallment
     * }
     */
    private function collectionContext(): array
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio, [
            'name' => 'Collection Owner',
        ]);
        $manager = $this->createUserWithRole('property_manager', $portfolio, [
            'name' => 'Collection Manager',
        ]);
        $tenant = $this->createUserWithRole('tenant', $portfolio, [
            'name' => 'Collection Tenant',
            'phone' => '+966500000001',
        ]);
        $root = $this->createAsset($portfolio, [
            'asset_type' => 'building',
            'title_en' => 'Collection Tower',
            'title_ar' => 'برج التحصيل',
            'rentable' => false,
        ]);
        $unit = $this->createAsset($portfolio, [
            'parent_id' => $root->id,
            'title_en' => 'Collection Unit',
            'title_ar' => 'وحدة التحصيل',
        ]);
        $this->assignManagerToAsset($manager, $root);
        $lease = $this->createLease(
            $portfolio,
            $this->createTenantProfile($portfolio, $tenant),
            $unit,
            $manager,
            ['code' => 'COLLECTION-LEASE'],
            syncInstallments: false,
        );
        $installment = LeaseInstallment::query()->create([
            'lease_id' => $lease->id,
            'sequence' => 1,
            'line_type' => 'rent',
            'label' => 'July rent',
            'period_start' => today()->startOfMonth(),
            'period_end' => today()->endOfMonth(),
            'due_date' => today()->subDays(5),
            'amount_due' => 1000,
            'amount_paid' => 200,
            'status' => 'overdue',
        ]);

        return compact(
            'portfolio',
            'owner',
            'manager',
            'tenant',
            'root',
            'lease',
            'installment',
        );
    }

    /** @param array<string, mixed> $overrides */
    private function followUpPayload(User $assignee, array $overrides = []): array
    {
        return array_merge([
            'contact_method' => 'phone',
            'outcome' => 'contacted',
            'contacted_at' => now()->subMinute()->format('Y-m-d\TH:i'),
            'assigned_to_user_id' => $assignee->id,
            'next_follow_up_on' => today()->addDay()->toDateString(),
            'promised_amount' => null,
            'promised_on' => null,
            'note' => 'Reached the tenant and agreed the next collection step.',
        ], $overrides);
    }
}
