<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Modules\Assets\Actions\CreateBuildingStructure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class BuildingStructureSetupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_owner_receives_a_scoped_bilingual_building_setup_form(): void
    {
        $portfolio = $this->createPortfolio([
            'name_en' => 'Owner Portfolio',
            'name_ar' => 'محفظة المالك',
        ]);
        $owner = $this->createUserWithRole('owner', $portfolio);
        $manager = $this->createUserWithRole('property_manager', $portfolio);
        $foreignPortfolio = $this->createPortfolio();
        $foreignManager = $this->createUserWithRole('property_manager', $foreignPortfolio);
        $portfolio->update(['owner_user_id' => $owner->id]);

        $this->actingAs($owner)
            ->get(route('assets.structure.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/assets/structure-create')
                ->where('app.translations.assets.builder.building_name_en', 'Building name (English)')
                ->where('app.translations.assets.builder.code_prefix', 'Building code prefix')
                ->where('buildingSetup.title', 'Set up a building')
                ->where('buildingSetup.action', route('assets.structure.store'))
                ->where('buildingSetup.initialValues.portfolio_id', (string) $portfolio->id)
                ->where('buildingSetup.initialValues.primary_owner_user_id', (string) $owner->id)
                ->where('buildingSetup.initialValues.primary_manager_user_id', (string) $owner->id)
                ->where('buildingSetup.options.managers', fn (mixed $options): bool => collect($options)
                    ->contains('value', (string) $manager->id)
                    && ! collect($options)->contains('value', (string) $foreignManager->id))
                ->where('buildingSetup.limits.records', 250));

        $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('assets.structure.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('app.locale', 'ar')
                ->where('app.translations.assets.builder.building_name_en', 'اسم المبنى بالإنجليزية')
                ->where('app.translations.assets.builder.code_prefix', 'رمز المبنى الأساسي')
                ->where('buildingSetup.title', 'إعداد مبنى')
                ->where('buildingSetup.options.portfolios.0.label', 'محفظة المالك'));
    }

    public function test_superadmin_builder_hides_inactive_portfolios_and_falls_back_to_an_active_one(): void
    {
        $superadmin = $this->createUserWithRole('superadmin');
        $active = $this->createPortfolio([
            'name_en' => 'Active Portfolio',
            'status' => 'active',
        ]);
        $inactive = $this->createPortfolio([
            'name_en' => 'Inactive Portfolio',
            'status' => 'inactive',
        ]);
        $owner = $this->createUserWithRole('owner', $active);
        $active->update(['owner_user_id' => $owner->id]);

        $this->actingAs($superadmin)
            ->get(route('assets.structure.create', ['portfolio_id' => $inactive->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('buildingSetup.initialValues.portfolio_id', (string) $active->id)
                ->where('buildingSetup.initialValues.primary_owner_user_id', (string) $owner->id)
                ->where('buildingSetup.initialValues.primary_manager_user_id', (string) $owner->id)
                ->where('buildingSetup.options.portfolios', fn (mixed $options): bool => collect($options)
                    ->pluck('value')
                    ->all() === [(string) $active->id]));
    }

    public function test_portfolio_setup_locks_building_creation_and_returns_to_the_next_step(): void
    {
        $portfolio = $this->createPortfolio([
            'name_en' => 'Setup Portfolio',
            'name_ar' => 'محفظة الإعداد',
        ]);
        $foreign = $this->createPortfolio();
        $superadmin = $this->createUserWithRole('superadmin');
        $owner = $this->createUserWithRole('owner', $portfolio);
        $manager = $this->createUserWithRole('property_manager', $portfolio);
        $foreignOwner = $this->createUserWithRole('owner', $foreign);
        $foreignManager = $this->createUserWithRole('property_manager', $foreign);
        $portfolio->update(['owner_user_id' => $owner->id]);
        $setupQuery = ['setup_portfolio_id' => $portfolio->id];

        $this->actingAs($superadmin)
            ->get(route('assets.structure.create', [
                'portfolio_id' => $portfolio->id,
                ...$setupQuery,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('buildingSetup.isSetup', true)
                ->where('buildingSetup.title', 'Register the first property for Setup Portfolio')
                ->where('buildingSetup.backHref', route('portfolios.show', $portfolio))
                ->where('buildingSetup.backLabel', 'Back to portfolio setup')
                ->where('buildingSetup.action', route('assets.structure.store', $setupQuery))
                ->where('buildingSetup.submitLabel', 'Create structure and continue')
                ->where('buildingSetup.options.portfolios', [[
                    'value' => (string) $portfolio->id,
                    'label' => 'Setup Portfolio',
                ]]));

        $this->actingAs($superadmin)
            ->post(
                route('assets.structure.store', $setupQuery),
                $this->payload($portfolio->id, $owner->id, $manager->id),
            )
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('portfolios.show', $portfolio))
            ->assertSessionHas(
                'success',
                'Business Tower was created. Continue setting up Setup Portfolio.',
            );

        $before = Asset::query()->count();
        $this->actingAs($superadmin)
            ->post(
                route('assets.structure.store', $setupQuery),
                $this->payload(
                    $foreign->id,
                    $foreignOwner->id,
                    $foreignManager->id,
                    ['code_prefix' => 'FORGED'],
                ),
            )
            ->assertNotFound();
        $this->assertSame($before, Asset::query()->count());
    }

    public function test_owner_creates_a_complete_building_hierarchy_in_one_transaction(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $manager = $this->createUserWithRole('property_manager', $portfolio);
        $portfolio->update(['owner_user_id' => $owner->id]);

        $response = $this->actingAs($owner)
            ->post(route('assets.structure.store'), $this->payload(
                $portfolio->id,
                $owner->id,
                $manager->id,
            ));

        $building = Asset::query()->where('code', 'BLDG-A')->firstOrFail();
        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('assets.show', $building));

        $this->assertSame(9, Asset::query()->where('portfolio_id', $portfolio->id)->count());
        $this->assertSame(2, $building->children()->count());
        $this->assertSame('building', $building->asset_type);
        $this->assertFalse($building->rentable);
        $this->assertSame(24.7136, $building->meta_json['map']['latitude']);
        $this->assertSame('North Riyadh', $building->meta_json['map']['zone_en']);

        $firstFloor = Asset::query()->where('code', 'BLDG-A-F01')->firstOrFail();
        $this->assertSame($building->id, $firstFloor->parent_id);
        $this->assertSame('Floor 1', $firstFloor->title_en);
        $this->assertSame('الطابق 1', $firstFloor->title_ar);
        $this->assertSame(360.0, (float) $firstFloor->area);
        $this->assertSame(3, $firstFloor->children()->count());

        $unit = Asset::query()->where('code', 'BLDG-A-F01-U01')->firstOrFail();
        $this->assertSame($firstFloor->id, $unit->parent_id);
        $this->assertSame('Unit 101', $unit->title_en);
        $this->assertSame('الوحدة 101', $unit->title_ar);
        $this->assertSame('101', $unit->unit_label);
        $this->assertTrue($unit->rentable);
        $this->assertSame('vacant', $unit->occupancy_status);
        $this->assertSame(120.0, (float) $unit->area);
        $this->assertSame(18, $building->portfolio->assets()->withCount('stakeholders')->get()->sum('stakeholders_count'));

        $this->assertDatabaseHas('asset_stakeholders', [
            'asset_id' => $unit->id,
            'user_id' => $owner->id,
            'relationship_type' => 'owner',
            'ends_on' => null,
        ]);
        $this->assertDatabaseHas('asset_stakeholders', [
            'asset_id' => $unit->id,
            'user_id' => $manager->id,
            'relationship_type' => 'manager',
            'ends_on' => null,
        ]);
    }

    public function test_ground_floor_space_numbering_is_deterministic(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $manager = $this->createUserWithRole('property_manager', $portfolio);

        $this->actingAs($owner)
            ->post(route('assets.structure.store'), $this->payload(
                $portfolio->id,
                $owner->id,
                $manager->id,
                [
                    'code_prefix' => 'SHOP',
                    'floor_count' => 1,
                    'units_per_floor' => 2,
                    'floor_start' => 0,
                    'unit_type' => 'space',
                    'usage_type' => 'commercial',
                ],
            ))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('assets', [
            'code' => 'SHOP-F00',
            'asset_type' => 'floor',
            'title_en' => 'Ground floor',
            'title_ar' => 'الطابق الأرضي',
        ]);
        $this->assertDatabaseHas('assets', [
            'code' => 'SHOP-F00-U01',
            'asset_type' => 'space',
            'title_en' => 'Space 001',
            'title_ar' => 'المساحة 001',
            'unit_label' => '001',
        ]);
    }

    public function test_invalid_scope_duplicate_codes_and_oversized_plans_create_nothing(): void
    {
        $portfolio = $this->createPortfolio();
        $foreignPortfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $manager = $this->createUserWithRole('property_manager', $portfolio);
        $foreignManager = $this->createUserWithRole('property_manager', $foreignPortfolio);

        $this->actingAs($owner)
            ->post(route('assets.structure.store'), $this->payload(
                $portfolio->id,
                $owner->id,
                $foreignManager->id,
            ))
            ->assertSessionHasErrors('primary_manager_user_id');
        $this->assertDatabaseCount('assets', 0);

        $this->createAsset($portfolio, ['code' => 'BLDG-A-F01-U01']);
        $this->actingAs($owner)
            ->post(route('assets.structure.store'), $this->payload(
                $portfolio->id,
                $owner->id,
                $manager->id,
            ))
            ->assertSessionHasErrors('code_prefix');
        $this->assertDatabaseCount('assets', 1);

        $this->actingAs($owner)
            ->post(route('assets.structure.store'), $this->payload(
                $portfolio->id,
                $owner->id,
                $manager->id,
                [
                    'code_prefix' => 'TOO-LARGE',
                    'floor_count' => 30,
                    'units_per_floor' => 20,
                ],
            ))
            ->assertSessionHasErrors('units_per_floor');
        $this->assertDatabaseCount('assets', 1);
    }

    public function test_managers_and_tenants_cannot_use_the_building_setup_flow(): void
    {
        $portfolio = $this->createPortfolio();
        $manager = $this->createUserWithRole('property_manager', $portfolio);
        $tenant = $this->createUserWithRole('tenant', $portfolio);
        $owner = $this->createUserWithRole('owner', $portfolio);

        $this->actingAs($manager)
            ->get(route('assets.structure.create'))
            ->assertForbidden();
        $this->actingAs($manager)
            ->post(route('assets.structure.store'), $this->payload(
                $portfolio->id,
                $owner->id,
                $manager->id,
            ))
            ->assertForbidden();
        $this->actingAs($tenant)
            ->get(route('assets.structure.create'))
            ->assertForbidden();

        try {
            app(CreateBuildingStructure::class)->handle($manager, $this->payload(
                $portfolio->id,
                $owner->id,
                $manager->id,
            ));
            $this->fail('A manager bypassed the building setup access rule.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }

        $this->assertDatabaseCount('assets', 0);
    }

    public function test_direct_action_revalidates_input_and_active_stakeholders(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $manager = $this->createUserWithRole('property_manager', $portfolio, [
            'status' => 'inactive',
        ]);
        $structures = app(CreateBuildingStructure::class);

        try {
            $structures->handle($owner, $this->payload(
                $portfolio->id,
                $owner->id,
                $manager->id,
            ));
            $this->fail('An inactive manager was assigned by direct action reuse.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('primary_manager_user_id', $exception->errors());
        }

        $manager->update(['status' => 'active']);

        try {
            $structures->handle($owner, $this->payload(
                $portfolio->id,
                $owner->id,
                $manager->id,
                ['code_prefix' => 'lowercase code'],
            ));
            $this->fail('A malformed code prefix bypassed request validation.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('code_prefix', $exception->errors());
        }

        $this->assertDatabaseCount('assets', 0);
    }

    /** @return array<string, mixed> */
    private function payload(int $portfolioId, int $ownerId, int $managerId, array $overrides = []): array
    {
        return array_merge([
            'portfolio_id' => $portfolioId,
            'title_en' => 'Business Tower',
            'title_ar' => 'برج الأعمال',
            'code_prefix' => 'BLDG-A',
            'usage_type' => 'residential',
            'floor_count' => 2,
            'units_per_floor' => 3,
            'floor_start' => 1,
            'unit_type' => 'unit',
            'primary_owner_user_id' => $ownerId,
            'primary_manager_user_id' => $managerId,
            'valuation_amount' => 7500000,
            'currency' => 'SAR',
            'area' => 1400,
            'unit_area' => 120,
            'address' => 'King Fahd Road, Riyadh',
            'address_ar' => 'طريق الملك فهد، الرياض',
            'map_zone_en' => 'North Riyadh',
            'map_zone_ar' => 'شمال الرياض',
            'land_number' => 'LAND-42',
            'latitude' => 24.7136,
            'longitude' => 46.6753,
        ], $overrides);
    }
}
