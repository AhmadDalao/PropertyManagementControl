<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AuthenticationFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_guest_can_open_the_bilingual_login_workspace(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('dir="ltr"', false)
            ->assertInertia(fn (Assert $page) => $page
                ->component('auth/login')
                ->where('app.translations.login.sign_in', 'Sign in'));

        $this->get(route('login', ['locale' => 'ar']))
            ->assertOk()
            ->assertSee('dir="rtl"', false)
            ->assertInertia(fn (Assert $page) => $page
                ->component('auth/login')
                ->where('app.translations.login.sign_in', 'تسجيل الدخول'));
    }

    public function test_active_user_can_login_with_normalized_email_and_logout(): void
    {
        $user = User::factory()->create([
            'email' => 'operator@example.test',
            'password' => Hash::make('correct-password'),
            'status' => 'active',
            'last_login_at' => null,
        ]);

        $this->post(route('login.store'), [
            'email' => '  OPERATOR@EXAMPLE.TEST ',
            'password' => 'correct-password',
            'remember' => true,
        ])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('success');

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->last_login_at);

        $this->post(route('logout'))
            ->assertRedirect(route('home'))
            ->assertSessionHas('success');

        $this->assertGuest();
    }

    public function test_invalid_credentials_are_rejected_without_a_session(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('correct-password'),
            'status' => 'active',
        ]);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertNull($user->fresh()->last_login_at);
    }
}
