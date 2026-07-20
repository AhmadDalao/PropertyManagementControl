<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LocalizationAndWordingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_language_switch_persists_for_an_authenticated_user(): void
    {
        $owner = $this->createUserWithRole('owner', $this->createPortfolio(), [
            'preferred_locale' => 'en',
        ]);

        $this->actingAs($owner)
            ->from(route('dashboard').'?locale=en')
            ->post(route('locale.update', 'ar'))
            ->assertRedirect(route('dashboard'));

        $this->assertSame('ar', $owner->fresh()->preferred_locale);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('dir="rtl"', false)
            ->assertInertia(fn (Assert $page) => $page
                ->where('app.locale', 'ar')
                ->where('app.direction', 'rtl')
                ->where('app.translations.nav.dashboard', 'لوحة التحكم')
            );
    }

    public function test_language_switch_persists_for_a_guest_session(): void
    {
        $this->from(route('login'))
            ->post(route('locale.update', 'ar'))
            ->assertRedirect(route('login'));

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('dir="rtl"', false)
            ->assertInertia(fn (Assert $page) => $page
                ->where('app.locale', 'ar')
                ->where('app.direction', 'rtl')
                ->where('app.translations.nav.login', 'تسجيل الدخول')
            );
    }

    public function test_only_superadmin_can_open_the_wording_workspace(): void
    {
        $superadmin = $this->createUserWithRole('superadmin');
        $owner = $this->createUserWithRole('owner', $this->createPortfolio());

        $this->actingAs($superadmin)
            ->get(route('wording.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/wording/index')
                ->has('entries')
                ->where('groups', fn ($groups) => collect($groups)->contains('nav'))
            );

        $this->actingAs($owner)
            ->get(route('wording.index'))
            ->assertForbidden();
    }

    public function test_superadmin_can_customize_and_reset_bilingual_page_wording(): void
    {
        $superadmin = $this->createUserWithRole('superadmin');

        $this->actingAs($superadmin)
            ->put(route('wording.update'), [
                'group' => 'nav',
                'key' => 'dashboard',
                'english' => 'Control Center',
                'arabic' => 'مركز التحكم',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('label_overrides', [
            'portfolio_id' => null,
            'group_name' => 'nav',
            'override_key' => 'dashboard',
            'locale' => 'en',
            'value' => 'Control Center',
        ]);
        $this->assertDatabaseHas('label_overrides', [
            'portfolio_id' => null,
            'group_name' => 'nav',
            'override_key' => 'dashboard',
            'locale' => 'ar',
            'value' => 'مركز التحكم',
        ]);

        $this->withSession(['locale' => 'en'])
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('app.translations.nav.dashboard', 'Control Center')
            );

        $this->withSession(['locale' => 'ar'])
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('app.translations.nav.dashboard', 'مركز التحكم')
            );

        $this->delete(route('wording.destroy'), [
            'group' => 'nav',
            'key' => 'dashboard',
        ])->assertRedirect();

        $this->assertDatabaseMissing('label_overrides', [
            'group_name' => 'nav',
            'override_key' => 'dashboard',
        ]);
    }

    public function test_unknown_wording_keys_are_rejected(): void
    {
        $superadmin = $this->createUserWithRole('superadmin');

        $this->actingAs($superadmin)
            ->put(route('wording.update'), [
                'group' => 'unknown',
                'key' => 'unsafe',
                'english' => 'Bad override',
                'arabic' => 'تجاوز غير صالح',
            ])
            ->assertUnprocessable();

        $this->assertDatabaseCount('label_overrides', 0);
    }
}
