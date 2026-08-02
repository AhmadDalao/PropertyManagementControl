<?php

namespace App\Modules\EmailDelivery\Actions;

use App\Models\EmailDeliveryLog;
use App\Models\User;
use App\Modules\EmailDelivery\Support\EmailDeliveryType;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;
use Throwable;

final class RecordEmailDelivery
{
    public function __construct(private readonly EmailDeliveryType $types) {}

    public function starting(NotificationSending $event): void
    {
        if ($event->channel !== 'mail') {
            return;
        }

        $notificationId = $this->notificationId($event->notification);

        if ($notificationId === null) {
            return;
        }

        $log = EmailDeliveryLog::query()->firstOrNew([
            'notification_id' => $notificationId,
        ]);

        $log->fill([
            'portfolio_id' => $event->notifiable instanceof User
                ? $event->notifiable->portfolio_id
                : null,
            'user_id' => $event->notifiable instanceof User
                ? $event->notifiable->id
                : null,
            'notification_class' => $event->notification::class,
            'email_type' => $this->types->value($event->notification),
            'recipient_email' => $this->recipient($event->notifiable, $event->notification),
            'status' => 'processing',
            'mailer' => (string) config('mail.default', 'log'),
            'attempts' => $log->exists ? $log->attempts + 1 : 1,
            'started_at' => now(),
            'accepted_at' => null,
            'failed_at' => null,
            'error_message' => null,
        ])->save();
    }

    public function message(MessageSending $event): void
    {
        $notificationId = $event->data['__laravel_notification_id'] ?? null;

        if (! is_string($notificationId) || $notificationId === '') {
            return;
        }

        EmailDeliveryLog::query()
            ->where('notification_id', $notificationId)
            ->update([
                'subject' => Str::limit((string) $event->message->getSubject(), 255, ''),
                'recipient_email' => $this->messageRecipients($event),
                'updated_at' => now(),
            ]);
    }

    public function accepted(NotificationSent $event): void
    {
        if ($event->channel !== 'mail') {
            return;
        }

        $notificationId = $this->notificationId($event->notification);

        if ($notificationId === null) {
            return;
        }

        EmailDeliveryLog::query()
            ->where('notification_id', $notificationId)
            ->update([
                'status' => 'accepted',
                'transport_message_id' => $this->transportMessageId($event->response),
                'accepted_at' => now(),
                'failed_at' => null,
                'error_message' => null,
                'updated_at' => now(),
            ]);
    }

    public function failed(NotificationFailed $event): void
    {
        if ($event->channel !== 'mail') {
            return;
        }

        $notificationId = $this->notificationId($event->notification);

        if ($notificationId === null) {
            return;
        }

        $exception = $event->data['exception'] ?? null;
        $message = $exception instanceof Throwable
            ? $exception->getMessage()
            : trans('app.email_delivery.unknown_failure');

        EmailDeliveryLog::query()
            ->where('notification_id', $notificationId)
            ->update([
                'status' => 'failed',
                'failed_at' => now(),
                'accepted_at' => null,
                'error_message' => Str::limit(
                    preg_replace('/\s+/', ' ', trim($message)) ?: trans('app.email_delivery.unknown_failure'),
                    1000,
                    '',
                ),
                'updated_at' => now(),
            ]);
    }

    private function notificationId(Notification $notification): ?string
    {
        return $notification->id !== null && $notification->id !== ''
            ? $notification->id
            : null;
    }

    private function recipient(mixed $notifiable, Notification $notification): string
    {
        if ($notifiable instanceof User) {
            return $notifiable->email;
        }

        $route = is_object($notifiable) && method_exists($notifiable, 'routeNotificationFor')
            ? $notifiable->routeNotificationFor('mail', $notification)
            : null;

        if (is_string($route)) {
            return $route;
        }

        if (is_array($route)) {
            return (string) array_key_first($route);
        }

        return trans('app.email_delivery.unknown_recipient');
    }

    private function messageRecipients(MessageSending $event): string
    {
        $recipients = array_map(
            static fn ($address): string => $address->getAddress(),
            $event->message->getTo(),
        );

        return $recipients === []
            ? trans('app.email_delivery.unknown_recipient')
            : implode(', ', $recipients);
    }

    private function transportMessageId(mixed $response): ?string
    {
        if (! is_object($response) || ! method_exists($response, 'getMessageId')) {
            return null;
        }

        $messageId = $response->getMessageId();

        return is_string($messageId) && $messageId !== ''
            ? Str::limit($messageId, 255, '')
            : null;
    }
}
