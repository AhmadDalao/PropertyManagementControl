<?php

namespace App\Modules\Notifications\Notifications;

use App\Models\MaintenanceRequest;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

final class MaintenanceActivityNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $event,
        private readonly MaintenanceRequest $request,
        private readonly User $actor,
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
            'event' => $this->event,
            'title_en' => $this->copy('title', 'en'),
            'title_ar' => $this->copy('title', 'ar'),
            'body_en' => $this->copy('body', 'en'),
            'body_ar' => $this->copy('body', 'ar'),
            'url' => route('maintenance-requests.show', $this->request, false),
            'icon' => $this->icon(),
            'tone' => $this->tone(),
            'resource_type' => 'maintenance_request',
            'resource_id' => $this->request->id,
            'portfolio_id' => $this->request->portfolio_id,
            'asset_id' => $this->request->asset_id,
            'actor_user_id' => $this->actor->id,
        ];
    }

    private function copy(string $part, string $locale): string
    {
        return trans("app.notifications.events.{$this->event}.{$part}", [
            'actor' => $this->actor->name,
            'id' => $this->request->id,
            'request' => $this->request->title,
            'status' => trans("app.status.{$this->request->status}", locale: $locale),
        ], $locale);
    }

    private function icon(): string
    {
        return match ($this->event) {
            'maintenance_resolved',
            'maintenance_confirmed' => 'bi-check-circle',
            'maintenance_reopened',
            'maintenance_cancelled' => 'bi-exclamation-circle',
            'maintenance_tenant_comment' => 'bi-chat-left-text',
            default => 'bi-tools',
        };
    }

    private function tone(): string
    {
        return match ($this->event) {
            'maintenance_resolved',
            'maintenance_confirmed' => 'success',
            'maintenance_reopened',
            'maintenance_cancelled' => 'danger',
            'maintenance_tenant_comment',
            'maintenance_updated' => 'blue',
            default => 'warning',
        };
    }
}
