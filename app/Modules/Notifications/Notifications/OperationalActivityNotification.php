<?php

namespace App\Modules\Notifications\Notifications;

use App\Modules\Notifications\Data\OperationalNotificationData;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

final class OperationalActivityNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly OperationalNotificationData $data,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'event' => $this->data->event,
            'title_en' => $this->copy('title', 'en', $this->data->replacementsEn),
            'title_ar' => $this->copy('title', 'ar', $this->data->replacementsAr),
            'body_en' => $this->copy('body', 'en', $this->data->replacementsEn),
            'body_ar' => $this->copy('body', 'ar', $this->data->replacementsAr),
            'url' => $this->data->url,
            'icon' => $this->data->icon,
            'tone' => $this->data->tone,
            'resource_type' => $this->data->resourceType,
            'resource_id' => $this->data->resourceId,
            'portfolio_id' => $this->data->portfolioId,
            'actor_user_id' => $this->data->actorUserId,
        ];
    }

    /** @param array<string, scalar|null> $replacements */
    private function copy(string $part, string $locale, array $replacements): string
    {
        return trans(
            "app.notifications.events.{$this->data->event}.{$part}",
            $replacements,
            $locale,
        );
    }
}
