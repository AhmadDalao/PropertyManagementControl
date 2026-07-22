<?php

namespace App\Modules\Maintenance\Support;

use Illuminate\Validation\ValidationException;

final class MaintenanceTransitionGuard
{
    /** @var array<string, array<int, string>> */
    private const TRANSITIONS = [
        'open' => ['open', 'in_progress', 'resolved', 'cancelled'],
        'in_progress' => ['in_progress', 'open', 'resolved', 'cancelled'],
        'resolved' => ['resolved', 'open'],
        'cancelled' => ['cancelled', 'open'],
    ];

    /** @return array<int, string> */
    public function allowedStatuses(string $current): array
    {
        return self::TRANSITIONS[$current] ?? [$current];
    }

    public function ensureAllowed(string $current, string $target): void
    {
        if (in_array($target, $this->allowedStatuses($current), true)) {
            return;
        }

        throw ValidationException::withMessages([
            'status' => trans('app.errors.maintenance_transition_not_allowed'),
        ]);
    }
}
