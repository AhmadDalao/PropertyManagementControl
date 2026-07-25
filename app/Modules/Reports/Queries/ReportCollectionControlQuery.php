<?php

namespace App\Modules\Reports\Queries;

use App\Models\User;
use App\Modules\RentCollection\Queries\RentCollectionDirectoryQuery;
use App\Modules\RentCollection\Queries\RentCollectionInsightsQuery;

final readonly class ReportCollectionControlQuery
{
    public function __construct(
        private RentCollectionDirectoryQuery $directory,
        private RentCollectionInsightsQuery $insights,
    ) {}

    /**
     * @param  array{date_from:string,date_to:string,portfolio_id:int|null,property_id:int|null}  $filters
     * @return array{
     *     openCollectionCount:int,
     *     untrackedOverdueCount:int,
     *     followUpDueCount:int,
     *     brokenPromisesCount:int
     * }
     */
    public function handle(User $actor, array $filters): array
    {
        $query = $this->directory->base($actor);
        $this->directory->applyScope($query, $filters, $actor);
        $insights = $this->insights->get($query);

        return [
            'openCollectionCount' => (int) $insights['open_count'],
            'untrackedOverdueCount' => (int) $insights['untracked_overdue_count'],
            'followUpDueCount' => (int) $insights['follow_up_due_count'],
            'brokenPromisesCount' => (int) $insights['broken_promises_count'],
        ];
    }
}
