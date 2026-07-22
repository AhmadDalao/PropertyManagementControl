<?php

namespace App\Modules\Leases\Support;

use Illuminate\Validation\ValidationException;

final class LeaseTransitionGuard
{
    /** @var array<string, array<int, string>> */
    private const TRANSITIONS = [
        'draft' => ['draft', 'active', 'terminated'],
        'active' => ['active', 'expired', 'terminated'],
        'expired' => ['expired'],
        'terminated' => ['terminated'],
    ];

    public function ensureAllowed(string $current, string $target): void
    {
        if (in_array($target, $this->allowedStatuses($current), true)) {
            return;
        }

        throw ValidationException::withMessages([
            'status' => trans('app.errors.lease_transition_invalid', [
                'from' => trans("app.status.{$current}"),
                'to' => trans("app.status.{$target}"),
            ]),
        ]);
    }

    /** @return array<int, string> */
    public function allowedStatuses(string $current): array
    {
        return self::TRANSITIONS[$current] ?? [$current];
    }
}
