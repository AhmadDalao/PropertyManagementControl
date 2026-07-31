<?php

namespace App\Modules\Notifications\Presenters;

use Illuminate\Notifications\DatabaseNotification;

final class NotificationItemPresenter
{
    /** @return array<string, mixed> */
    public function present(DatabaseNotification $notification): array
    {
        $locale = app()->getLocale();
        $data = $notification->data;

        return [
            'id' => $notification->id,
            'event' => (string) ($data['event'] ?? ''),
            'title' => $this->localized($data, 'title', $locale),
            'body' => $this->localized($data, 'body', $locale),
            'icon' => (string) ($data['icon'] ?? 'bi-envelope'),
            'tone' => (string) ($data['tone'] ?? 'neutral'),
            'target_href' => $this->target($data['url'] ?? null),
            'read_href' => route('notifications.read', $notification->id, false),
            'read' => $notification->read_at !== null,
            'created_at' => $notification->created_at?->toIso8601String(),
        ];
    }

    /** @param array<string, mixed> $data */
    private function localized(array $data, string $field, string $locale): string
    {
        $primary = $data["{$field}_{$locale}"] ?? null;
        $fallback = $data["{$field}_".($locale === 'ar' ? 'en' : 'ar')] ?? null;

        return trim((string) ($primary ?: $fallback));
    }

    private function target(mixed $url): string
    {
        $target = is_string($url) ? trim($url) : '';

        return str_starts_with($target, '/') ? $target : '/notifications';
    }
}
