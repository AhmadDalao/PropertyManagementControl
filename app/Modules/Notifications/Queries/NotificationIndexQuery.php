<?php

namespace App\Modules\Notifications\Queries;

use App\Models\User;
use App\Modules\Notifications\Presenters\NotificationItemPresenter;
use Illuminate\Notifications\DatabaseNotification;

final readonly class NotificationIndexQuery
{
    public function __construct(private NotificationItemPresenter $items) {}

    /** @return array<string, mixed> */
    public function handle(User $actor, string $status): array
    {
        $base = $actor->notifications();
        $counts = [
            'all' => (clone $base)->count(),
            'unread' => (clone $base)->whereNull('read_at')->count(),
            'read' => (clone $base)->whereNotNull('read_at')->count(),
        ];
        $notifications = $actor->notifications()
            ->when($status === 'unread', fn ($query) => $query->whereNull('read_at'))
            ->when($status === 'read', fn ($query) => $query->whereNotNull('read_at'))
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(
                fn (DatabaseNotification $notification): array => $this->items->present($notification),
            );

        return [
            'filters' => ['status' => $status],
            'counts' => $counts,
            'notificationItems' => $notifications,
        ];
    }
}
