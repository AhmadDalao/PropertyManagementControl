<?php

namespace App\Modules\ActionCenter\Support;

use Carbon\CarbonInterface;

final class ActionCenterItemState
{
    public function dueState(?CarbonInterface $date): string
    {
        if ($date === null) {
            return 'unscheduled';
        }

        if ($date->isToday()) {
            return 'today';
        }

        return $date->isPast() ? 'overdue' : 'upcoming';
    }

    public function rank(string $priority): int
    {
        return match ($priority) {
            'critical' => 0,
            'high' => 1,
            default => 2,
        };
    }
}
