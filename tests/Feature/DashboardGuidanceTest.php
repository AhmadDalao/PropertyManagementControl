<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardGuidanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_owner_dashboard_shows_setup_next_actions(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);

        $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('dashboard')
                ->where('mode', 'portfolio')
                ->where('nextActions', fn ($actions) => collect($actions)->contains('label', 'Create users')
                    && collect($actions)->contains('label', 'Create assets')
                    && collect($actions)->contains('label', 'Create profiles')
                    && collect($actions)->contains('href', '/users/create')
                    && collect($actions)->contains('href', '/assets/create')
                    && collect($actions)->contains('href', '/tenants/create')
                    && ! collect($actions)->contains('label', 'Create portfolio'))
            );
    }
}
