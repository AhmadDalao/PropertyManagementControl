<?php

namespace App\Modules\Payments\Support;

use Illuminate\Validation\ValidationException;

final class PaymentTransitionGuard
{
    /** @var array<string, array<int, string>> */
    private const TRANSITIONS = [
        'pending' => ['pending', 'posted', 'void'],
        'posted' => ['posted', 'pending', 'void'],
        'void' => ['void'],
    ];

    public function ensureAllowed(string $current, string $target): void
    {
        if (in_array($target, $this->allowedStatuses($current), true)) {
            return;
        }

        throw ValidationException::withMessages([
            'status' => trans('app.errors.payment_transition_invalid', [
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
