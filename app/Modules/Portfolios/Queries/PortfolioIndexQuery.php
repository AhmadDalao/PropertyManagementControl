<?php

namespace App\Modules\Portfolios\Queries;

use App\Models\Portfolio;
use App\Models\User;
use App\Modules\Portfolios\Support\PortfolioAccess;
use App\Modules\Portfolios\Support\PortfolioOptions;
use App\Modules\Shared\TableQuery;
use App\Support\PortfolioModules;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class PortfolioIndexQuery
{
    public function __construct(
        private readonly PortfolioDirectoryQuery $directory,
        private readonly PortfolioInsightsQuery $insights,
        private readonly PortfolioAccess $access,
        private readonly TableQuery $tables,
    ) {}

    /** @return array<string, mixed> */
    public function handle(Request $request, User $actor): array
    {
        $filters = $this->directory->filters($request);
        $baseQuery = $this->directory->base($actor);
        $portfolios = $this->directory->listing(clone $baseQuery, $actor);
        $this->directory->applyFilters($portfolios, $filters);

        return [
            'portfolios' => $this->tables->paginate($portfolios, $filters, [
                'created_at',
                'name_en',
                'code',
                'status',
                'city',
            ]),
            'portfolioInsights' => $this->insights->get(clone $baseQuery, $actor),
            'filters' => $filters,
            'counts' => $this->directory->statusCounts(clone $baseQuery, $filters),
            'canCreate' => $this->access->canCreate($actor),
            'canUpdate' => $actor->hasAnyRole(['superadmin', 'owner']),
            'canArchive' => $this->access->canArchive($actor),
            'moduleDefinitions' => PortfolioModules::definitions(),
            'statusOptions' => PortfolioOptions::STATUSES,
        ];
    }

    /** @return Builder<Portfolio> */
    public function forExport(Request $request, User $actor): Builder
    {
        $filters = $this->directory->filters($request);
        $query = $this->directory->listing($this->directory->base($actor), $actor);
        $this->directory->applyFilters($query, $filters);

        return $query;
    }

    /** @return array<int, Portfolio> */
    public function search(User $actor, string $search, int $limit = 5): array
    {
        return $this->directory->search($actor, $search, $limit);
    }

    public function exactCode(User $actor, string $code): ?Portfolio
    {
        return $this->directory->exactCode($actor, $code);
    }
}
