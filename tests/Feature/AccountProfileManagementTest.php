<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AccountProfileManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_authenticated_user_can_open_and_update_profile(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio, [
            'name' => 'Old Owner',
            'phone' => '+966500000001',
            'preferred_locale' => 'en',
        ]);

        $this->actingAs($owner)
            ->get(route('profile.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/profile/index')
                ->where('profile.email', $owner->email)
            );

        $this->actingAs($owner)
            ->put(route('profile.update'), [
                'name' => 'Updated Owner',
                'phone' => '+966500000002',
                'preferred_locale' => 'ar',
            ])
            ->assertRedirect(route('profile.index'));

        $owner->refresh();
        $this->assertSame('Updated Owner', $owner->name);
        $this->assertSame('+966500000002', $owner->phone);
        $this->assertSame('ar', $owner->preferred_locale);
    }

    public function test_forced_password_user_can_change_password_without_current_password(): void
    {
        $portfolio = $this->createPortfolio();
        $tenant = $this->createUserWithRole('tenant', $portfolio, [
            'force_password_reset' => true,
            'password' => Hash::make('temporary-secret'),
        ]);

        $this->actingAs($tenant)
            ->put(route('profile.password'), [
                'current_password' => '',
                'password' => 'new-secure-password',
                'password_confirmation' => 'new-secure-password',
            ])
            ->assertRedirect(route('profile.index'));

        $tenant->refresh();
        $this->assertFalse($tenant->force_password_reset);
        $this->assertTrue(Hash::check('new-secure-password', $tenant->password));
    }

    public function test_regular_password_change_requires_the_current_password(): void
    {
        $portfolio = $this->createPortfolio();
        $manager = $this->createUserWithRole('property_manager', $portfolio, [
            'force_password_reset' => false,
            'password' => Hash::make('known-password'),
        ]);

        $this->actingAs($manager)
            ->from(route('profile.index'))
            ->put(route('profile.password'), [
                'current_password' => 'wrong-password',
                'password' => 'new-secure-password',
                'password_confirmation' => 'new-secure-password',
            ])
            ->assertRedirect(route('profile.index'))
            ->assertSessionHasErrors('current_password');

        $manager->refresh();
        $this->assertTrue(Hash::check('known-password', $manager->password));
    }

    public function test_admin_password_reset_marks_user_for_forced_reset(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenant = $this->createUserWithRole('tenant', $portfolio, [
            'force_password_reset' => false,
        ]);

        $this->actingAs($owner)
            ->put(route('users.update', $tenant), [
                'name' => $tenant->name,
                'phone' => $tenant->phone,
                'preferred_locale' => 'en',
                'status' => 'active',
                'role' => 'tenant',
                'password' => 'temporary-reset',
            ])
            ->assertRedirect(route('users.index'));

        $tenant->refresh();
        $this->assertTrue($tenant->force_password_reset);
        $this->assertTrue(Hash::check('temporary-reset', $tenant->password));
    }

    public function test_manager_cannot_manage_owner_account_in_the_same_portfolio(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $manager = $this->createUserWithRole('property_manager', $portfolio);

        $this->actingAs($manager)
            ->put(route('users.update', $owner), [
                'name' => 'Bad edit',
                'phone' => $owner->phone,
                'preferred_locale' => 'en',
                'status' => 'active',
                'role' => 'tenant',
                'password' => '',
            ])
            ->assertForbidden();

        $this->assertTrue($owner->fresh()->hasRole('owner'));
        $this->assertSame($owner->name, $owner->fresh()->name);
    }

    public function test_inactive_user_cannot_login(): void
    {
        $portfolio = $this->createPortfolio();
        $tenant = $this->createUserWithRole('tenant', $portfolio, [
            'email' => 'inactive@example.test',
            'status' => 'inactive',
            'password' => Hash::make('password'),
        ]);

        $this->post(route('login.store'), [
            'email' => $tenant->email,
            'password' => 'password',
        ])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
