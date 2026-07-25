<?php

namespace App\Modules\SystemReadiness\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReadinessTestNotification extends Notification
{
    use Queueable;

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(trans('app.readiness.test_email_subject'))
            ->greeting(trans('app.readiness.test_email_greeting'))
            ->line(trans('app.readiness.test_email_body'))
            ->action(trans('app.readiness.open_readiness'), url('/system/readiness'))
            ->line(trans('app.readiness.test_email_footer'));
    }
}
