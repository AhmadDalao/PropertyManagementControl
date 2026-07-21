<?php

namespace App\Modules\Maintenance\Support;

use App\Models\MaintenanceRequest;
use Carbon\CarbonInterface;

class MaintenanceSchedule
{
    public function dueAtForPriority(string $priority): CarbonInterface
    {
        return match ($priority) {
            'urgent' => now()->addHours(24),
            'high' => now()->addDays(2),
            'low' => now()->addDays(7),
            default => now()->addDays(4),
        };
    }

    public function nextDueAt(
        MaintenanceRequest $request,
        string $priority,
        string $status,
        string $previousPriority
    ): ?CarbonInterface {
        if (in_array($status, ['resolved', 'cancelled'], true)) {
            return $request->due_at;
        }

        if ($priority !== $previousPriority || ! $request->due_at) {
            return $this->dueAtForPriority($priority);
        }

        return $request->due_at;
    }
}
