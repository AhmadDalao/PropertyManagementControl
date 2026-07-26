<?php

namespace App\Modules\Dashboard\Queries;

use App\Models\User;
use App\Modules\Dashboard\Support\DashboardPropertyContext;

final readonly class OperationsPropertyPerformanceQuery
{
    public function __construct(
        private PropertyPerformanceDatasetQuery $dataset,
    ) {}

    /** @return array<int, array<string, mixed>> */
    public function forUser(User $actor, DashboardPropertyContext $context): array
    {
        return collect($this->dataset->forUser($actor, $context))
            ->sortBy([
                ['attention_score', 'desc'],
                ['title_en', 'asc'],
            ])
            ->take(8)
            ->values()
            ->all();
    }
}
