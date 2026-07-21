<?php

namespace App\Modules\Audit\Queries;

use App\Models\User;
use App\Modules\Audit\Presenters\AuditActivityPresenter;
use App\Modules\Audit\Requests\AuditIndexRequest;
use App\Modules\Audit\Support\AuditAccess;
use App\Modules\Audit\Support\AuditPortfolioScope;
use App\Modules\Audit\Support\AuditSubjectRegistry;
use App\Modules\Shared\PortfolioScope;
use App\Modules\Shared\TableQuery;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Models\Activity;

class AuditLogQuery
{
    public function __construct(
        private readonly AuditAccess $access,
        private readonly AuditPortfolioScope $activityScope,
        private readonly AuditSubjectRegistry $subjects,
        private readonly AuditActivityPresenter $presenter,
        private readonly PortfolioScope $portfolios,
        private readonly TableQuery $tables,
    ) {}

    /** @return array<string, mixed> */
    public function handle(AuditIndexRequest $request, User $actor): array
    {
        $filters = $request->filters();
        $baseQuery = $this->baseQuery($actor, $filters);
        $activities = (clone $baseQuery)->with(['causer', 'subject']);
        $this->applyFilters($activities, $filters);

        $facetQuery = clone $baseQuery;
        $this->applyFilters($facetQuery, $filters, false);
        $counts = $this->counts($facetQuery, $filters);

        $paginator = $this->tables
            ->paginate($activities, $filters, ['created_at', 'event', 'description'])
            ->through(fn (Activity $activity): array => $this->presenter->row($activity));

        return [
            'activities' => $paginator,
            'auditInsights' => $this->insights($counts),
            'filters' => $filters,
            'counts' => $counts,
            'portfolioOptions' => $this->portfolios->options($actor),
            'subjectTypeOptions' => $this->subjects->options($actor),
            'causerOptions' => $this->causerOptions($baseQuery),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<Activity>
     */
    public function filtered(User $actor, array $filters): Builder
    {
        $query = $this->baseQuery($actor, $filters)->with(['causer', 'subject']);
        $this->applyFilters($query, $filters);

        return $query;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<Activity>
     */
    private function baseQuery(User $actor, array $filters): Builder
    {
        $portfolioId = $this->filterId($filters['portfolio_id'] ?? null);
        $causerId = $this->filterId($filters['causer_id'] ?? null);
        $this->access->ensureFilters($actor, $portfolioId, $causerId);

        return $this->activityScope->apply(Activity::query(), $actor, $portfolioId);
    }

    /**
     * @param  Builder<Activity>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder $query, array $filters, bool $includeEvent = true): void
    {
        if ($includeEvent) {
            $this->tables->exact($query, $filters, 'event');
        }

        $this->tables->dateRange($query, $filters, 'created_at');

        if (($filters['subject_type'] ?? 'all') !== 'all') {
            $query->whereIn(
                'subject_type',
                $this->subjects->typeValues((string) $filters['subject_type']),
            );
        }

        $causerId = $this->filterId($filters['causer_id'] ?? null);

        if ($causerId !== null) {
            $query
                ->whereIn('causer_type', $this->subjects->typeValues('user'))
                ->where('causer_id', $causerId);
        }

        $this->tables->search($query, (string) $filters['search'], [
            'description',
            'event',
            'log_name',
            static function (Builder $query, string $search): void {
                if (ctype_digit($search)) {
                    $query
                        ->orWhere('subject_id', (int) $search)
                        ->orWhere('causer_id', (int) $search)
                        ->orWhere('id', (int) $search);
                }
            },
        ]);
    }

    /**
     * @param  Builder<Activity>  $query
     * @param  array<string, mixed>  $filters
     * @return array<int, array{label:string,value:int,filter:array<string, string>,active:bool}>
     */
    private function counts(Builder $query, array $filters): array
    {
        $activeEvent = (string) ($filters['event'] ?? 'all');
        $counts = [[
            'label' => trans('app.audit.events.all'),
            'value' => (clone $query)->count(),
            'filter' => ['event' => 'all'],
            'active' => $activeEvent === 'all',
        ]];

        foreach (['created', 'updated', 'deleted'] as $event) {
            $counts[] = [
                'label' => trans("app.audit.events.{$event}"),
                'value' => (clone $query)->where('event', $event)->count(),
                'filter' => ['event' => $event],
                'active' => $activeEvent === $event,
            ];
        }

        return $counts;
    }

    /**
     * @param  array<int, array{label:string,value:int,filter:array<string, string>,active:bool}>  $counts
     * @return array{total:int,created:int,updated:int,deleted:int}
     */
    private function insights(array $counts): array
    {
        $values = collect($counts)->mapWithKeys(
            fn (array $count): array => [$count['filter']['event'] => $count['value']],
        );

        return [
            'total' => (int) $values->get('all', 0),
            'created' => (int) $values->get('created', 0),
            'updated' => (int) $values->get('updated', 0),
            'deleted' => (int) $values->get('deleted', 0),
        ];
    }

    /**
     * @param  Builder<Activity>  $query
     * @return array<int, array{id:int,name:string}>
     */
    private function causerOptions(Builder $query): array
    {
        $causerIds = (clone $query)
            ->whereIn('causer_type', $this->subjects->typeValues('user'))
            ->whereNotNull('causer_id')
            ->distinct()
            ->limit(250)
            ->pluck('causer_id');

        return User::query()
            ->whereIn('id', $causerIds)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
            ])
            ->all();
    }

    private function filterId(mixed $value): ?int
    {
        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }
}
