<?php

namespace App\Modules\ActionCenter\Contracts;

use App\Models\User;

interface ActionCenterSource
{
    public function type(): string;

    public function module(): string;

    /** @param array<string, mixed> $filters */
    public function count(User $actor, array $filters): int;

    /**
     * @param  array<string, mixed>  $filters
     * @return list<array<string, mixed>>
     */
    public function items(User $actor, array $filters, int $limit): array;
}
