<?php

namespace App\Modules\Notifications\Presenters;

use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;

final readonly class NotificationSummaryPresenter
{
    public function __construct(private NotificationItemPresenter $items) {}

    /** @return array<string, mixed> */
    public function present(?User $actor): array
    {
        if (! $actor) {
            return ['unread_count' => 0, 'recent' => []];
        }

        return [
            'unread_count' => $actor->unreadNotifications()->count(),
            'recent' => $actor->notifications()
                ->latest()
                ->limit(5)
                ->get()
                ->map(fn (DatabaseNotification $notification): array => $this->items->present($notification))
                ->values()
                ->all(),
        ];
    }
}
