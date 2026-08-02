<?php

namespace App\Modules\EmailDelivery\Support;

use App\Modules\Authentication\Notifications\ResetPasswordNotification;
use App\Modules\SystemReadiness\Notifications\ReadinessTestNotification;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

final class EmailDeliveryType
{
    public function value(Notification $notification): string
    {
        return match (true) {
            $notification instanceof ResetPasswordNotification => 'password_reset',
            $notification instanceof ReadinessTestNotification => 'readiness_test',
            default => Str::snake(class_basename($notification)),
        };
    }

    public function label(string $type): string
    {
        $key = "app.email_delivery.types.{$type}";

        return trans()->has($key)
            ? trans($key)
            : Str::of($type)->replace('_', ' ')->headline()->toString();
    }
}
