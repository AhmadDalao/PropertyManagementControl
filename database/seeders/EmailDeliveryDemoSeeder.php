<?php

namespace Database\Seeders;

use App\Models\EmailDeliveryLog;
use App\Models\User;
use Illuminate\Database\Seeder;

final class EmailDeliveryDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            return;
        }

        $superadmin = User::query()
            ->where('email', 'superadmin@propertycontrol.test')
            ->first();
        $owner = User::query()
            ->where('email', 'owner@propertycontrol.test')
            ->first();

        if (! $superadmin || ! $owner) {
            return;
        }

        EmailDeliveryLog::query()->updateOrCreate(
            ['notification_id' => '00000000-0000-4000-8000-000000000101'],
            [
                'portfolio_id' => null,
                'user_id' => $superadmin->id,
                'notification_class' => 'App\\Modules\\SystemReadiness\\Notifications\\ReadinessTestNotification',
                'email_type' => 'readiness_test',
                'recipient_email' => $superadmin->email,
                'subject' => 'Property portal readiness test',
                'status' => 'accepted',
                'mailer' => 'array',
                'transport_message_id' => 'demo-accepted-message',
                'attempts' => 1,
                'started_at' => now()->subMinutes(12),
                'accepted_at' => now()->subMinutes(12)->addSecond(),
                'failed_at' => null,
                'error_message' => null,
            ],
        );

        EmailDeliveryLog::query()->updateOrCreate(
            ['notification_id' => '00000000-0000-4000-8000-000000000102'],
            [
                'portfolio_id' => $owner->portfolio_id,
                'user_id' => $owner->id,
                'notification_class' => 'App\\Modules\\Authentication\\Notifications\\ResetPasswordNotification',
                'email_type' => 'password_reset',
                'recipient_email' => $owner->email,
                'subject' => 'Reset your property portal password',
                'status' => 'failed',
                'mailer' => 'smtp',
                'transport_message_id' => null,
                'attempts' => 2,
                'started_at' => now()->subMinutes(5),
                'accepted_at' => null,
                'failed_at' => now()->subMinutes(5)->addSecond(),
                'error_message' => 'Demo transport rejection for interface testing.',
            ],
        );
    }
}
