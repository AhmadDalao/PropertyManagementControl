<?php

namespace App\Modules\Users\Queries;

use App\Models\User;
use App\Modules\Shared\PortfolioScope;
use App\Modules\Shared\TableQuery;
use App\Modules\Users\Presenters\UserTableRowPresenter;
use App\Modules\Users\Support\UserOptions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

final class UserIndexQuery
{
    public function __construct(
        private readonly UserDirectoryQuery $directory,
        private readonly UserInsightsQuery $insights,
        private readonly UserTableRowPresenter $rows,
        private readonly PortfolioScope $portfolios,
        private readonly TableQuery $tables,
    ) {}

    /** @return array<string, mixed> */
    public function handle(Request $request, User $actor): array
    {
        $filters = $this->directory->filters($request, $actor);
        $baseQuery = $this->directory->base($actor);
        $summaryQuery = clone $baseQuery;
        $this->directory->applyPortfolio($summaryQuery, $filters);
        $countQuery = clone $summaryQuery;
        $this->directory->applyRole($countQuery, (string) $filters['role']);
        $users = $this->directory->listing(clone $baseQuery);
        $this->directory->apply($users, $filters);

        return [
            'users' => $this->tables->paginate($users, $filters, [
                'created_at',
                'name',
                'email',
                'status',
                'last_login_at',
            ])->through(fn (User $user): array => $this->rows->present($user)),
            'filters' => $filters,
            'counts' => $this->localizedCounts($this->tables->statusCounts(
                $countQuery,
                UserOptions::STATUSES,
                $filters,
            )),
            'portfolioOptions' => $this->portfolios->options($actor),
            'roleOptions' => UserOptions::visibleRoles($actor),
            'statusOptions' => UserOptions::STATUSES,
            'userInsights' => $this->insights->get($summaryQuery),
        ];
    }

    /** @return Builder<User> */
    public function forExport(Request $request, User $actor): Builder
    {
        $filters = $this->directory->filters($request, $actor);
        $query = $this->directory->base($actor)->with(['portfolio', 'roles']);
        $this->directory->apply($query, $filters);

        return $query;
    }

    /**
     * @param  array<int, array<string, mixed>>  $counts
     * @return array<int, array<string, mixed>>
     */
    private function localizedCounts(array $counts): array
    {
        return collect($counts)->map(function (array $count): array {
            $status = (string) data_get($count, 'filter.status', 'all');
            $count['label'] = $status === 'all'
                ? trans('app.users.all')
                : trans("app.status.{$status}");

            return $count;
        })->all();
    }
}
