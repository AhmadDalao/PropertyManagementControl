<?php

namespace Tests\Feature;

use App\Models\EmailDeliveryLog;
use App\Modules\Authentication\Notifications\ResetPasswordNotification;
use App\Modules\SystemReadiness\Notifications\ReadinessTestNotification;
use Database\Seeders\EmailDeliveryDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use RuntimeException;
use Tests\TestCase;

final class EmailDeliveryControlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        config()->set('mail.default', 'array');
    }

    public function test_mail_notifications_record_transport_acceptance_without_message_bodies(): void
    {
        $portfolio = $this->createPortfolio();
        $admin = $this->createUserWithRole('superadmin', $portfolio, [
            'email' => 'mail-control@example.test',
        ]);

        $admin->notify(new ReadinessTestNotification);
        $admin->notify(new ResetPasswordNotification('test-token'));

        $this->assertDatabaseCount('email_delivery_logs', 2);
        $this->assertDatabaseHas('email_delivery_logs', [
            'portfolio_id' => $portfolio->id,
            'user_id' => $admin->id,
            'email_type' => 'readiness_test',
            'recipient_email' => 'mail-control@example.test',
            'status' => 'accepted',
            'mailer' => 'array',
            'attempts' => 1,
            'error_message' => null,
        ]);
        $this->assertDatabaseHas('email_delivery_logs', [
            'email_type' => 'password_reset',
            'status' => 'accepted',
        ]);

        $logs = EmailDeliveryLog::query()->get();
        $this->assertTrue($logs->every(
            fn (EmailDeliveryLog $log): bool => $log->subject !== null
                && $log->accepted_at !== null
                && $log->started_at !== null
                && $log->meta_json === null,
        ));
    }

    public function test_failed_attempts_are_sanitized_and_retries_increment_the_same_record(): void
    {
        $admin = $this->createUserWithRole('superadmin', attributes: [
            'email' => 'failed-mail@example.test',
        ]);
        $notification = new ReadinessTestNotification;
        $notification->id = (string) Str::uuid();

        event(new NotificationSending($admin, $notification, 'mail'));
        event(new NotificationFailed(
            $admin,
            $notification,
            'mail',
            ['exception' => new RuntimeException("SMTP rejected\nrecipient")],
        ));
        event(new NotificationSending($admin, $notification, 'mail'));
        event(new NotificationFailed(
            $admin,
            $notification,
            'mail',
            ['exception' => new RuntimeException('SMTP rejected again')],
        ));

        $this->assertDatabaseHas('email_delivery_logs', [
            'notification_id' => $notification->id,
            'status' => 'failed',
            'attempts' => 2,
            'error_message' => 'SMTP rejected again',
        ]);
        $this->assertDatabaseCount('email_delivery_logs', 1);
    }

    public function test_only_superadmins_can_browse_open_and_export_delivery_evidence(): void
    {
        $superadmin = $this->createUserWithRole('superadmin');
        $owner = $this->createUserWithRole('owner', $this->createPortfolio());
        $log = $this->delivery([
            'recipient_email' => 'owner@example.test',
            'subject' => 'Reset your password',
            'status' => 'accepted',
            'email_type' => 'password_reset',
        ]);

        $this->actingAs($superadmin)
            ->get(route('email-delivery.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/email-delivery/index')
                ->has('deliveries.data', 1)
                ->where('deliveries.data.0.id', $log->id)
                ->where('insights.total', 1)
                ->where('insights.accepted', 1));

        $this->actingAs($superadmin)
            ->get(route('email-delivery.show', $log))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/email-delivery/show')
                ->where('delivery.notification_id', $log->notification_id)
                ->where('delivery.status_label', 'Accepted'));

        $this->actingAs($superadmin)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where(
                    'reportLibrary.3.cards',
                    fn ($cards): bool => collect($cards)->contains(
                        fn (array $card): bool => $card['key'] === 'email-delivery'
                            && str_contains($card['openHref'], '/system/email-delivery')
                            && str_contains($card['downloads'][0]['href'], '/system/email-delivery/export'),
                    ),
                ));

        $this->actingAs($superadmin)
            ->withSession(['locale' => 'ar'])
            ->get('/documentation/email-delivery')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/documentation/show')
                ->where('guide.slug', 'email-delivery')
                ->where('guide.title', 'إرسال البريد'));

        $workbook = $this->actingAs($superadmin)
            ->get(route('email-delivery.export'))
            ->assertOk()
            ->assertHeader(
                'content-type',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            );
        $worksheet = $this->xlsxWorksheetXml($workbook);
        $this->assertStringContainsString('owner@example.test', $worksheet);
        $this->assertStringContainsString('Reset your password', $worksheet);

        foreach ([
            route('email-delivery.index'),
            route('email-delivery.show', $log),
            route('email-delivery.export'),
        ] as $url) {
            $this->actingAs($owner)->get($url)->assertForbidden();
        }
    }

    public function test_filters_pagination_and_arabic_copy_remain_consistent(): void
    {
        $superadmin = $this->createUserWithRole('superadmin');

        foreach (range(1, 12) as $index) {
            $this->delivery([
                'recipient_email' => "recipient-{$index}@example.test",
                'subject' => "Message {$index}",
                'status' => $index % 3 === 0 ? 'failed' : 'accepted',
                'email_type' => $index % 2 === 0
                    ? 'password_reset'
                    : 'readiness_test',
            ]);
        }

        $this->actingAs($superadmin)
            ->withSession(['locale' => 'ar'])
            ->get(route('email-delivery.index', [
                'status' => 'accepted',
                'email_type' => 'password_reset',
                'search' => 'recipient',
                'per_page' => 10,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/email-delivery/index')
                ->where('app.direction', 'rtl')
                ->where('app.translations.nav.email_delivery', 'إرسال البريد')
                ->where('app.translations.email_delivery.title', 'إرسال البريد')
                ->where('filters.status', 'accepted')
                ->where('filters.email_type', 'password_reset')
                ->where('deliveries.per_page', 10)
                ->where('deliveries.total', 4)
                ->has('deliveries.data', 4)
                ->where('deliveries.data.0.status_label', 'مقبول'));

        $this->actingAs($superadmin)
            ->get(route('email-delivery.index', ['per_page' => 10]))
            ->assertInertia(fn (Assert $page) => $page
                ->where('deliveries.total', 12)
                ->where('deliveries.last_page', 2)
                ->has('deliveries.data', 10));
    }

    public function test_local_delivery_examples_are_idempotent_and_contain_no_credentials(): void
    {
        $portfolio = $this->createPortfolio();
        $this->createUserWithRole('superadmin', attributes: [
            'email' => 'superadmin@propertycontrol.test',
        ]);
        $this->createUserWithRole('owner', $portfolio, [
            'email' => 'owner@propertycontrol.test',
        ]);

        $this->seed(EmailDeliveryDemoSeeder::class);
        $this->seed(EmailDeliveryDemoSeeder::class);

        $this->assertDatabaseCount('email_delivery_logs', 2);
        $this->assertTrue(
            EmailDeliveryLog::query()->get()->every(
                fn (EmailDeliveryLog $log): bool => $log->meta_json === null
                    && ! str_contains(strtolower((string) $log->error_message), 'password'),
            ),
        );
    }

    /** @param array<string, mixed> $attributes */
    private function delivery(array $attributes = []): EmailDeliveryLog
    {
        return EmailDeliveryLog::query()->create(array_merge([
            'notification_id' => (string) Str::uuid(),
            'notification_class' => ReadinessTestNotification::class,
            'email_type' => 'readiness_test',
            'recipient_email' => 'recipient@example.test',
            'subject' => 'Readiness test',
            'status' => 'processing',
            'mailer' => 'array',
            'attempts' => 1,
            'started_at' => now(),
        ], $attributes));
    }
}
