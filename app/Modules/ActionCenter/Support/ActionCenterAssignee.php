<?php

namespace App\Modules\ActionCenter\Support;

use App\Models\User;

final class ActionCenterAssignee
{
    /** @param array<string, mixed> $filters */
    public function value(array $filters, User $actor): int|string|null
    {
        $value = (string) ($filters['assignee'] ?? 'all');

        if ($value === 'all') {
            return null;
        }

        if ($value === 'me') {
            return $actor->id;
        }

        return $value === 'unassigned' ? $value : (int) $value;
    }
}
