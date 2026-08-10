<?php

namespace Tests\Feature;

use App\Models\InfrastructureSetting;
use App\Modules\InfrastructureSettings\Actions\ApplyInfrastructureSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

final class InfrastructureSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_only_superadmin_can_open_or_update_infrastructure_settings(): void
    {
        $superadmin = $this->createUserWithRole('superadmin');
        $owner = $this->createUserWithRole('owner', $this->createPortfolio());

        $this->actingAs($superadmin)
            ->get(route('infrastructure-settings.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/infrastructure-settings/index')
                ->has('statusChecks', 3)
                ->where('settings.mail_enabled', false)
                ->where('settings.password_configured', false)
                ->where('settings.smtp_ready', false)
                ->where('settings.scheduler_artisan_path', base_path('artisan'))
                ->missing('settings.mail_password'));

        $this->actingAs($owner)
            ->get(route('infrastructure-settings.index'))
            ->assertForbidden();

        $this->actingAs($owner)
            ->put(route('infrastructure-settings.update'), $this->completePayload())
            ->assertForbidden();
    }

    public function test_superadmin_can_save_a_disabled_draft_without_a_password(): void
    {
        $superadmin = $this->createUserWithRole('superadmin');

        $this->actingAs($superadmin)
            ->put(route('infrastructure-settings.update'), [
                ...$this->completePayload(),
                'mail_enabled' => false,
                'mail_password' => '',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $settings = InfrastructureSetting::query()->sole();
        $this->assertFalse($settings->mail_enabled);
        $this->assertNull($settings->mail_password);
        $this->assertSame('/opt/alt/php84/usr/bin/php', $settings->scheduler_php_binary);
    }

    public function test_enabled_smtp_is_encrypted_applied_at_runtime_and_never_exposed(): void
    {
        $superadmin = $this->createUserWithRole('superadmin');
        $secret = 'smtp-secret-that-must-never-leak';

        $this->actingAs($superadmin)
            ->put(route('infrastructure-settings.update'), [
                ...$this->completePayload(),
                'mail_password' => $secret,
            ])
            ->assertRedirect();

        $settings = InfrastructureSetting::query()->sole();
        $rawPassword = DB::table('infrastructure_settings')->value('mail_password');
        $this->assertSame($secret, $settings->mail_password);
        $this->assertIsString($rawPassword);
        $this->assertNotSame($secret, $rawPassword);
        $this->assertStringNotContainsString($secret, (string) $rawPassword);

        app(ApplyInfrastructureSettings::class)->handle();
        $this->assertSame('smtp', config('mail.default'));
        $this->assertSame('smtp.hostinger.com', config('mail.mailers.smtp.host'));
        $this->assertSame(465, config('mail.mailers.smtp.port'));
        $this->assertSame($secret, config('mail.mailers.smtp.password'));
        $this->assertSame(
            '/opt/alt/php84/usr/bin/php',
            config('operations.scheduler_php_binary'),
        );

        $this->actingAs($superadmin)
            ->get(route('infrastructure-settings.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('settings.password_configured', true)
                ->where('settings.smtp_ready', true)
                ->missing('settings.mail_password'));

        $activity = Activity::query()
            ->where('event', 'infrastructure_settings_updated')
            ->sole();
        $this->assertTrue($activity->properties->get('mail_password_changed'));
        $this->assertStringNotContainsString($secret, $activity->properties->toJson());
    }

    public function test_blank_password_preserves_the_secret_and_explicit_clear_removes_it(): void
    {
        $superadmin = $this->createUserWithRole('superadmin');
        $secret = 'preserve-this-smtp-secret';

        $this->actingAs($superadmin)
            ->put(route('infrastructure-settings.update'), [
                ...$this->completePayload(),
                'mail_password' => $secret,
            ])
            ->assertRedirect();
        $encrypted = DB::table('infrastructure_settings')->value('mail_password');

        $this->actingAs($superadmin)
            ->put(route('infrastructure-settings.update'), [
                ...$this->completePayload(),
                'mail_from_name' => 'Property Operations',
                'mail_password' => '',
            ])
            ->assertRedirect();

        $this->assertSame(
            $encrypted,
            DB::table('infrastructure_settings')->value('mail_password'),
        );

        $this->actingAs($superadmin)
            ->put(route('infrastructure-settings.update'), [
                ...$this->completePayload(),
                'mail_enabled' => false,
                'mail_password' => '',
                'clear_mail_password' => true,
            ])
            ->assertRedirect();

        $this->assertNull(InfrastructureSetting::query()->sole()->mail_password);
    }

    public function test_smtp_cannot_be_enabled_with_incomplete_or_cleared_credentials(): void
    {
        $superadmin = $this->createUserWithRole('superadmin');

        $this->actingAs($superadmin)
            ->from(route('infrastructure-settings.index'))
            ->put(route('infrastructure-settings.update'), [
                ...$this->completePayload(),
                'mail_host' => '',
                'mail_password' => '',
            ])
            ->assertRedirect(route('infrastructure-settings.index'))
            ->assertSessionHasErrors(['mail_host', 'mail_password']);

        $this->assertDatabaseCount('infrastructure_settings', 0);
    }

    public function test_arabic_settings_page_is_translated_and_rtl(): void
    {
        $superadmin = $this->createUserWithRole('superadmin');

        $this->actingAs($superadmin)
            ->withSession(['locale' => 'ar'])
            ->get(route('infrastructure-settings.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('app.locale', 'ar')
                ->where('app.direction', 'rtl')
                ->where('app.translations.nav.infrastructure_settings', 'إعدادات البنية التشغيلية')
                ->where('app.translations.infrastructure_settings.title', 'إعدادات البنية التشغيلية')
                ->where('app.translations.infrastructure_settings.save', 'حفظ الإعدادات'));
    }

    /** @return array<string, mixed> */
    private function completePayload(): array
    {
        return [
            'mail_enabled' => true,
            'mail_host' => 'smtp.hostinger.com',
            'mail_port' => 465,
            'mail_scheme' => 'smtps',
            'mail_username' => 'no-reply@property.example.com',
            'mail_password' => 'temporary-test-secret',
            'clear_mail_password' => false,
            'mail_from_address' => 'no-reply@property.example.com',
            'mail_from_name' => 'Property Control',
            'scheduler_php_binary' => '/opt/alt/php84/usr/bin/php',
        ];
    }
}
