<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Document;
use App\Models\Lease;
use App\Models\MaintenanceRequest;
use App\Models\Payment;
use App\Models\Portfolio;
use App\Models\TenantProfile;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DemoDataSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_data_seeder_creates_a_complete_local_property_cycle(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $this->seed(DemoDataSeeder::class);

        $this->assertDatabaseHas('users', ['email' => 'superadmin@propertycontrol.test']);
        $this->assertDatabaseHas('users', ['email' => 'owner@propertycontrol.test']);
        $this->assertDatabaseHas('users', ['email' => 'manager@propertycontrol.test']);
        $this->assertDatabaseHas('users', ['email' => 'tenant@propertycontrol.test']);
        $this->assertSame(2, Portfolio::query()->count());
        $this->assertGreaterThanOrEqual(8, Asset::query()->count());
        $this->assertGreaterThanOrEqual(2, TenantProfile::query()->count());
        $this->assertGreaterThanOrEqual(4, Lease::query()->count());
        $this->assertGreaterThanOrEqual(4, Payment::query()->count());
        $this->assertGreaterThanOrEqual(4, MaintenanceRequest::query()->count());
        $this->assertGreaterThanOrEqual(4, Document::query()->count());
        $this->assertDatabaseHas('documents', [
            'documentable_type' => 'lease',
            'type' => 'lease_contract',
        ]);
        $this->assertDatabaseMissing('documents', [
            'documentable_type' => Lease::class,
        ]);
    }

    public function test_demo_seed_command_requires_explicit_fresh_demo_flag(): void
    {
        $this->artisan('property:seed-demo-data')
            ->expectsOutput('Use --fresh-demo so the demo dataset starts from a clean local database.')
            ->assertExitCode(1);
    }

    public function test_demo_seed_command_is_blocked_in_production(): void
    {
        $this->app->detectEnvironment(fn (): string => 'production');

        $this->artisan('property:seed-demo-data --fresh-demo')
            ->expectsOutput('Demo data seeding is blocked in production.')
            ->assertExitCode(1);
    }
}
