<?php

namespace App\Modules\Notifications\Actions;

use App\Models\User;

final class MarkNotificationsRead
{
    public function one(User $actor, string $notificationId): string
    {
        $notification = $actor->notifications()->whereKey($notificationId)->firstOrFail();
        $notification->markAsRead();
        $target = $notification->data['url'] ?? null;

        return is_string($target) && str_starts_with($target, '/')
            ? $target
            : route('notifications.index', absolute: false);
    }

    public function all(User $actor): void
    {
        $actor->unreadNotifications()->update(['read_at' => now()]);
    }
}
