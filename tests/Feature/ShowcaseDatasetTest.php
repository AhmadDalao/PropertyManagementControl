<?php

namespace Tests\Feature;

use App\Jobs\GenerateShowcaseBuilding;
use App\Models\Asset;
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
use App\Modules\ShowcaseData\Actions\BuildShowcaseProperty;
use App\Modules\ShowcaseData\Actions\RetryShowcaseDataset;
use App\Modules\ShowcaseData\Actions\StartShowcaseDataset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ShowcaseDatasetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Storage::fake('local');
        Queue::fake();
    }

    public function test_only_superadmin_can_control_the_data_lab(): void
    {
        $superadmin = $this->createUserWithRole('superadmin');
        $owner = $this->createUserWithRole('owner', $this->createPortfolio());

        $this->actingAs($superadmin)
            ->get(route('showcase-data.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/showcase-data/index')
                ->where('targets.buildings', 40)
                ->where('targets.documents', 960)
                ->where('canGenerate', true)
                ->has('datasets.data'));

        $this->actingAs($owner)
            ->get(route('showcase-data.index'))
            ->assertForbidden();
    }

    public function test_opening_the_data_lab_does_not_mutate_legacy_records(): void
    {
        $superadmin = $this->createUserWithRole('superadmin');
        $portfolio = $this->createPortfolio(['code' => 'SHOW-LEGACY-READ']);
        $owner = $this->createUserWithRole('owner', $portfolio, [
            'email' => 'legacy-owner@example.test',
            'status' => 'active',
        ]);
        $portfolio->update(['owner_user_id' => $owner->id]);

        $this->actingAs($superadmin)
            ->get(route('showcase-data.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('legacyCandidates', 1));

        $this->assertDatabaseCount('showcase_datasets', 0);
        $this->assertDatabaseHas('portfolios', [
            'id' => $portfolio->id,
            'showcase_dataset_id' => null,
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $owner->id,
            'email' => 'legacy-owner@example.test',
            'status' => 'active',
            'showcase_dataset_id' => null,
        ]);
    }

    public function test_dataset_history_is_paginated_instead_of_rendered_without_a_limit(): void
    {
        $superadmin = $this->createUserWithRole('superadmin');

        foreach (range(1, 7) as $index) {
            ShowcaseDataset::query()->create([
                'key' => "PURGED-{$index}",
                'name' => "Purged dataset {$index}",
                'status' => 'purged',
                'target_properties' => 40,
                'generated_properties' => 0,
            ]);
        }

        $this->actingAs($superadmin)
            ->get(route('showcase-data.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('datasets.data', 6)
                ->where('datasets.total', 7)
                ->where('datasets.last_page', 2));
    }

    public function test_generation_is_queued_and_showcase_accounts_cannot_log_in(): void
    {
        $superadmin = $this->createUserWithRole('superadmin');

        $this->actingAs($superadmin)
            ->post(route('showcase-data.store'))
            ->assertRedirect(route('showcase-data.index'));

        Queue::assertPushed(GenerateShowcaseBuilding::class, 40);
        $this->assertDatabaseCount('showcase_datasets', 1);
        $this->assertSame(5, Portfolio::query()->whereNotNull('showcase_dataset_id')->count());
        $this->assertSame(25, User::query()->whereNotNull('showcase_dataset_id')->count());
        $this->assertSame(0, User::query()->whereNotNull('showcase_dataset_id')->where('status', 'active')->count());
        $this->assertSame(0, User::query()->whereNotNull('showcase_dataset_id')->where('email', 'not like', '%.invalid')->count());

        $showcaseUser = User::query()->whereNotNull('showcase_dataset_id')->firstOrFail();
        $showcaseUser->update(['password' => 'known-showcase-password']);
        auth()->logout();

        $this->post(route('login.store'), [
            'email' => $showcaseUser->email,
            'password' => 'known-showcase-password',
        ])->assertSessionHasErrors('email');

        $this->actingAs($superadmin)
            ->post(route('showcase-data.store'))
            ->assertSessionHasErrors('showcase');
    }

    public function test_full_dataset_hits_exact_targets_maps_only_buildings_and_purges_safely(): void
    {
        $superadmin = $this->createUserWithRole('superadmin');
        $start = app(StartShowcaseDataset::class);
        $builder = app(BuildShowcaseProperty::class);
        $dataset = $start->handle($superadmin);

        foreach (range(0, 39) as $buildingIndex) {
            $builder->handle($dataset->id, $buildingIndex);
        }

        $dataset->refresh();
        $portfolioIds = $dataset->portfolios()->pluck('id');
        $leaseIds = Lease::query()->whereIn('portfolio_id', $portfolioIds)->pluck('id');

        $this->assertSame('complete', $dataset->status);
        $this->assertSame(40, $dataset->generated_properties);
        $this->assertSame(5, $portfolioIds->count());
        $this->assertSame(40, Asset::query()->whereIn('portfolio_id', $portfolioIds)->where('asset_type', 'building')->count());
        $this->assertSame(160, Asset::query()->whereIn('portfolio_id', $portfolioIds)->where('asset_type', 'floor')->count());
        $this->assertSame(640, Asset::query()->whereIn('portfolio_id', $portfolioIds)->where('asset_type', 'unit')->count());
        $this->assertSame(5, $dataset->users()->role('owner')->count());
        $this->assertSame(20, $dataset->users()->role('property_manager')->count());
        $this->assertSame(480, $dataset->users()->role('tenant')->count());
        $this->assertSame(480, TenantProfile::query()->whereIn('portfolio_id', $portfolioIds)->count());
        $this->assertSame(480, $leaseIds->count());
        $this->assertSame(400, Lease::query()->whereIn('id', $leaseIds)->where('status', 'active')->count());
        $this->assertSame(40, Lease::query()->whereIn('id', $leaseIds)->where('status', 'expired')->count());
        $this->assertSame(20, Lease::query()->whereIn('id', $leaseIds)->where('status', 'terminated')->count());
        $this->assertSame(20, Lease::query()->whereIn('id', $leaseIds)->where('status', 'draft')->count());
        $terminated = Lease::query()->whereIn('id', $leaseIds)->where('status', 'terminated')->firstOrFail();
        $this->assertTrue($terminated->ends_at->isAfter($terminated->started_at));
        $this->assertSame(5760, LeaseInstallment::query()->whereIn('lease_id', $leaseIds)->count());
        $this->assertSame(1600, Payment::query()->whereIn('portfolio_id', $portfolioIds)->count());
        $this->assertSame(320, MaintenanceRequest::query()->whereIn('portfolio_id', $portfolioIds)->count());
        $this->assertSame(240, ExpenseEntry::query()->whereIn('portfolio_id', $portfolioIds)->count());
        $this->assertSame(960, Document::query()->whereIn('portfolio_id', $portfolioIds)->count());
        $this->assertSame(0, Document::query()->whereIn('portfolio_id', $portfolioIds)->where('mime_type', '!=', 'application/pdf')->count());

        $sampleDocument = Document::query()->whereIn('portfolio_id', $portfolioIds)->firstOrFail();
        Storage::disk('local')->assertExists($sampleDocument->file_path);
        $this->assertSame('%PDF-', substr((string) Storage::disk('local')->get($sampleDocument->file_path), 0, 5));

        $builder->handle($dataset->id, 0);
        $this->assertSame(840, Asset::query()->whereIn('portfolio_id', $portfolioIds)->count());
        $this->assertSame(480, Lease::query()->whereIn('portfolio_id', $portfolioIds)->count());
        $this->assertSame(1600, Payment::query()->whereIn('portfolio_id', $portfolioIds)->count());

        Queue::fake();
        $dataset->update(['status' => 'failed', 'generated_properties' => 39]);
        $retried = app(RetryShowcaseDataset::class)->handle($dataset->fresh());
        $this->assertSame('complete', $retried->status);
        $this->assertSame(40, $retried->generated_properties);
        Queue::assertNothingPushed();

        $this->actingAs($superadmin)
            ->post(route('showcase-data.retry', $retried))
            ->assertStatus(422);

        $this->actingAs($superadmin)
            ->get(route('property-map.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('propertyMap.summary.total', 40)
                ->where('propertyMap.summary.payload_limit', 40)
                ->has('propertyMap.assets', 40)
                ->where('propertyMap.assets', fn ($assets) => collect($assets)->every(
                    fn ($asset) => $asset['asset_type'] === 'building'
                        && $asset['is_showcase'] === true
                        && $asset['children_count'] === 20
                        && $asset['rentable_children_count'] === 16
                        && $asset['active_leases_count'] === 10
                        && $asset['open_requests_count'] === 6
                )));

        $this->actingAs($superadmin)
            ->delete(route('showcase-data.destroy', $dataset), [
                'confirmation' => 'wrong confirmation',
            ])
            ->assertSessionHasErrors('confirmation');

        $this->actingAs($superadmin)
            ->delete(route('showcase-data.destroy', $dataset), [
                'confirmation' => trans('app.showcase.confirmation'),
            ])
            ->assertRedirect(route('showcase-data.index'));

        $dataset->refresh();
        $this->assertSame('purged', $dataset->status);
        $this->assertSame(0, Portfolio::query()->where('showcase_dataset_id', $dataset->id)->count());
        $this->assertSame(0, User::query()->where('showcase_dataset_id', $dataset->id)->count());
        Storage::disk('local')->assertMissing("showcase/{$dataset->key}");
    }
}
