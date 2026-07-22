<?php

namespace App\Modules\Expenses\Presenters;

use App\Models\ExpenseEntry;
use App\Models\User;
use App\Modules\Expenses\Queries\ExpenseDetailQuery;
use App\Modules\Shared\ResourcePresenter;

final class ExpenseDetailPresenter
{
    public function __construct(
        private readonly ExpenseDetailQuery $query,
        private readonly ExpenseDetailHeaderPresenter $header,
        private readonly ExpenseDetailOverviewPresenter $overview,
        private readonly ResourcePresenter $resources,
    ) {}

    /** @return array<string, mixed> */
    public function present(ExpenseEntry $expense, User $actor): array
    {
        $data = $this->query->get($expense, $actor);

        return [
            'header' => $this->header->present($data),
            'decisionCards' => $this->overview->decisionCards($data),
            'stats' => $this->overview->stats($data),
            'sections' => $this->overview->sections($data),
            'related' => [],
            'documents' => [],
            'timeline' => $this->resources->activityTimeline($expense),
        ];
    }
}
