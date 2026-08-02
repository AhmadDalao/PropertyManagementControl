<?php

namespace App\Modules\ActionCenter\Queries;

use App\Models\User;
use App\Modules\ActionCenter\Presenters\ActionCenterReportPresenter;

final readonly class ActionCenterReportQuery
{
    public function __construct(
        private ActionCenterIndexQuery $actions,
        private ActionCenterReportPresenter $presenter,
    ) {}

    /**
     * @param array{
     *     search:string,type:string,priority:string,assignee:string,
     *     portfolio_id:int|null,property_id:int|null,per_page:int,page:int
     * } $filters
     * @return array<string, mixed>
     */
    public function handle(User $actor, array $filters): array
    {
        return $this->presenter->present(
            $actor,
            $filters,
            $this->actions->exportItems($actor, $filters),
        );
    }
}
