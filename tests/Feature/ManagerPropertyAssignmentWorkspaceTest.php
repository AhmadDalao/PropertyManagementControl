<?php

namespace Tests\Feature;

use App\Models\AssetStakeholder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ManagerPropertyAssignmentWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_owner_can_open_manager_assignment_workspace_and_profile_action(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $manager = $this->createUserWithRole('property_manager', $portfolio);
        $property = $this->createAsset($portfolio, [
            'asset_type' => 'property',
            'title_en' => 'North Portfolio',
            'code' => 'NORTH-PROPERTY',
        ]);
        $building = $this->createAsset($portfolio, [
            'parent_id' => $property->id,
            'asset_type' => 'building',
            'title_en' => 'North Tower',
            'code' => 'NORTH-TOWER',
        ]);
        $this->createAsset($portfolio, [
            'parent_id' => $building->id,
            'asset_type' => 'unit',
            'code' => 'NORTH-UNIT',
        ]);
        $this->assignManagerToAsset($manager, $building);

        $this->actingAs($owner)
            ->get(route('users.show', $manager))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where(
                'detailPage.header.actions',
                fn ($actions): bool => collect($actions)->contains(
                    'href',
                    route('users.property-assignments.edit', $manager),
                ),
            ));

        $this->actingAs($owner)
            ->get(route('users.property-assignments.edit', $manager))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/users/property-assignments')
                ->where('assignmentPage.manager.id', $manager->id)
                ->has('assignmentPage.properties', 2)
                ->where('assignmentPage.selected_ids', [$building->id])
                ->where('assignmentPage.properties', fn ($properties): bool => collect($properties)
                    ->contains(fn ($item): bool => $item['id'] === $building->id
                        && $item['selected'] === true
                        && $item['children_count'] === 1)));
    }

    public function test_assignment_save_replaces_primary_manager_and_changes_descendant_scope(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $manager = $this->createUserWithRole('property_manager', $portfolio);
        $peer = $this->createUserWithRole('property_manager', $portfolio);
        $visibleRoot = $this->createAsset($portfolio, [
            'asset_type' => 'building',
            'code' => 'ASSIGN-VISIBLE',
        ]);
        $visibleUnit = $this->createAsset($portfolio, [
            'parent_id' => $visibleRoot->id,
            'code' => 'ASSIGN-VISIBLE-UNIT',
        ]);
        $removedRoot = $this->createAsset($portfolio, [
            'asset_type' => 'building',
            'code' => 'ASSIGN-REMOVED',
        ]);
        $this->assignManagerToAsset($peer, $visibleRoot);
        $this->assignManagerToAsset($manager, $removedRoot);

        $this->actingAs($owner)
            ->put(route('users.property-assignments.update', $manager), [
                'asset_ids' => [$visibleRoot->id],
            ])
            ->assertRedirect(route('users.show', $manager))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('asset_stakeholders', [
            'asset_id' => $visibleRoot->id,
            'user_id' => $manager->id,
            'relationship_type' => 'manager',
            'is_primary' => true,
            'ends_on' => null,
        ]);
        $this->assertNotNull(
            AssetStakeholder::query()
                ->where('asset_id', $visibleRoot->id)
                ->where('user_id', $peer->id)
                ->where('relationship_type', 'manager')
                ->value('ends_on'),
        );
        $this->assertNotNull(
            AssetStakeholder::query()
                ->where('asset_id', $removedRoot->id)
                ->where('user_id', $manager->id)
                ->where('relationship_type', 'manager')
                ->value('ends_on'),
        );

        $this->actingAs($manager)
            ->get(route('assets.index', ['per_page' => 100]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('auth.user.property_scope.restricted', true)
                ->where('auth.user.property_scope.has_assignments', true)
                ->where('assets.total', 2)
                ->where('assets.data', fn ($assets): bool => collect($assets)
                    ->pluck('id')
                    ->sort()
                    ->values()
                    ->all() === collect([$visibleRoot->id, $visibleUnit->id])
                    ->sort()
                    ->values()
                    ->all()));
    }

    public function test_assignment_workspace_rejects_managers_and_foreign_owners(): void
    {
        $portfolio = $this->createPortfolio();
        $foreignPortfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $manager = $this->createUserWithRole('property_manager', $portfolio);
        $foreignOwner = $this->createUserWithRole('owner', $foreignPortfolio);

        $this->actingAs($manager)
            ->get(route('users.property-assignments.edit', $manager))
            ->assertForbidden();
        $this->actingAs($foreignOwner)
            ->get(route('users.property-assignments.edit', $manager))
            ->assertForbidden();
        $this->actingAs($foreignOwner)
            ->put(route('users.property-assignments.update', $manager), ['asset_ids' => []])
            ->assertForbidden();

        $this->actingAs($owner)
            ->put(route('users.property-assignments.update', $manager), [
                'asset_ids' => [$this->createAsset($foreignPortfolio)->id],
            ])
            ->assertStatus(422);
    }

    public function test_unassigned_manager_create_routes_are_blocked_before_rendering_forms(): void
    {
        $portfolio = $this->createPortfolio();
        $manager = $this->createUserWithRole('property_manager', $portfolio);

        foreach ([
            route('users.create'),
            route('assets.create'),
            route('tenants.create'),
            route('leases.create'),
            route('payments.create'),
            route('maintenance-requests.create'),
            route('expenses.create'),
            route('documents.create'),
        ] as $url) {
            $this->actingAs($manager)->get($url)->assertForbidden();
        }

        $this->actingAs($manager)
            ->get(route('assets.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('auth.user.property_scope.restricted', true)
                ->where('auth.user.property_scope.has_assignments', false));
    }
}
