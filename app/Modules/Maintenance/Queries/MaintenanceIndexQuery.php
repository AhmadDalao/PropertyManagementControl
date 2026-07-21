<?php

namespace App\Modules\Maintenance\Queries;

use App\Models\MaintenanceRequest;
use App\Models\MaintenanceUpdate;
use App\Models\TenantProfile;
use App\Models\User;
use App\Modules\Maintenance\Support\MaintenanceAccess;
use App\Modules\Maintenance\Support\MaintenanceOptions;
use App\Modules\Shared\PortfolioScope;
use App\Modules\Shared\TableQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class MaintenanceIndexQuery
{
    public function __construct(
        private readonly MaintenanceAccess $access,
        private readonly PortfolioScope $portfolios,
        private readonly TableQuery $tables,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(Request $request, User $actor): array
    {
        $filters = $this->tables->filters($request, [
            'status' => 'all',
            'category' => 'all',
            'priority' => 'all',
            'date_from' => '',
            'date_to' => '',
        ]);

        return $actor->hasRole('tenant')
            ? $this->tenantPayload($actor, $filters)
            : $this->managerPayload($actor, $filters);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function tenantPayload(User $actor, array $filters): array
    {
        $tenant = TenantProfile::query()
            ->where('user_id', $actor->id)
            ->with(['leases.leaseable'])
            ->first();
        $baseQuery = MaintenanceRequest::query()->when(
            $tenant,
            fn ($query) => $query->where('tenant_profile_id', $tenant->id),
            fn ($query) => $query->whereRaw('1 = 0')
        );
        $requests = (clone $baseQuery)->with([
            'asset',
            'tenantProfile.user',
            'assignedTo',
            'updates.user',
        ]);

        $this->applyFilters($requests, $filters);
        $this->tables->search($requests, $filters['search'], [
            'title',
            'description',
            'category',
            fn ($query, $search, $like) => $query->orWhereHas(
                'asset',
                fn ($assetQuery) => $assetQuery
                    ->where('title_en', 'like', $like)
                    ->orWhere('code', 'like', $like)
            ),
        ]);

        return [
            ...$this->commonPayload($baseQuery, $filters, includeFinancials: false),
            'mode' => 'tenant',
            'requests' => $this->paginate($requests, $filters, publicOnly: true),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function managerPayload(User $actor, array $filters): array
    {
        $this->access->ensureManager($actor);
        $baseQuery = $this->portfolios->apply(MaintenanceRequest::query(), $actor);
        $requests = (clone $baseQuery)->with([
            'asset',
            'tenantProfile.user',
            'assignedTo',
            'updates.user',
            'expenses',
        ]);

        $this->tables->exact($requests, $filters, 'portfolio_id');
        $this->applyFilters($requests, $filters);
        $this->tables->search($requests, $filters['search'], [
            'title',
            'description',
            'category',
            'internal_notes',
            fn ($query, $search, $like) => $query->orWhereHas(
                'asset',
                fn ($assetQuery) => $assetQuery
                    ->where('title_en', 'like', $like)
                    ->orWhere('code', 'like', $like)
            ),
            fn ($query, $search, $like) => $query->orWhereHas(
                'tenantProfile.user',
                fn ($userQuery) => $userQuery
                    ->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like)
            ),
            fn ($query, $search, $like) => $query->orWhereHas(
                'assignedTo',
                fn ($userQuery) => $userQuery
                    ->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like)
            ),
        ]);

        return [
            ...$this->commonPayload($baseQuery, $filters, includeFinancials: true),
            'mode' => 'manager',
            'requests' => $this->paginate($requests, $filters, publicOnly: false),
        ];
    }

    /**
     * @param  Builder<MaintenanceRequest>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        foreach (['status', 'category', 'priority'] as $filter) {
            $this->tables->exact($query, $filters, $filter);
        }

        $this->tables->dateRange($query, $filters, 'created_at');
    }

    /**
     * @param  Builder<MaintenanceRequest>  $query
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    private function paginate(Builder $query, array $filters, bool $publicOnly): LengthAwarePaginator
    {
        return $this->tables->paginate($query, $filters, [
            'created_at',
            'requested_at',
            'status',
            'priority',
            'category',
        ])->through(fn (MaintenanceRequest $request) => $this->tableRow($request, $publicOnly));
    }

    /**
     * @param  Builder<MaintenanceRequest>  $baseQuery
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function commonPayload(
        Builder $baseQuery,
        array $filters,
        bool $includeFinancials
    ): array {
        return [
            'maintenanceInsights' => $this->insights($baseQuery, $includeFinancials),
            'filters' => $filters,
            'counts' => $this->tables->statusCounts(
                $baseQuery,
                MaintenanceOptions::STATUSES,
                $filters,
            ),
            'categoryOptions' => MaintenanceOptions::CATEGORIES,
            'priorityOptions' => MaintenanceOptions::PRIORITIES,
            'statusOptions' => MaintenanceOptions::STATUSES,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function tableRow(MaintenanceRequest $request, bool $publicOnly): array
    {
        $relations = ['asset', 'tenantProfile.user', 'assignedTo', 'updates.user'];

        if (! $publicOnly) {
            $relations[] = 'expenses';
        }

        $request->loadMissing($relations);
        $updates = $request->updates
            ->when($publicOnly, fn ($collection) => $collection->where('is_public_comment', true))
            ->sortByDesc('created_at')
            ->map(fn (MaintenanceUpdate $update) => [
                'id' => $update->id,
                'user' => $update->user?->name,
                'status_from' => $update->status_from,
                'status_to' => $update->status_to,
                'is_public_comment' => $update->is_public_comment,
                'comment' => $update->comment,
                'created_at' => $update->created_at?->toIso8601String(),
            ])
            ->values()
            ->all();

        return [
            'id' => $request->id,
            'title' => $request->title,
            'description' => $request->description,
            'status' => $request->status,
            'category' => $request->category,
            'priority' => $request->priority,
            'created_at' => $request->created_at?->toIso8601String(),
            'requested_at' => $request->requested_at?->toIso8601String(),
            'due_at' => $request->due_at?->toIso8601String(),
            'resolved_at' => $request->resolved_at?->toIso8601String(),
            'is_overdue' => $request->due_at
                ? $request->due_at->isPast() && ! in_array($request->status, ['resolved', 'cancelled'], true)
                : false,
            'assigned_to_user_id' => $request->assigned_to_user_id,
            'assigned_to' => $request->assignedTo ? [
                'id' => $request->assignedTo->id,
                'name' => $request->assignedTo->name,
            ] : null,
            'internal_notes' => $publicOnly ? null : $request->internal_notes,
            'asset' => $request->asset ? [
                'id' => $request->asset->id,
                'title_en' => $request->asset->title_en,
                'title_ar' => $request->asset->title_ar,
                'code' => $request->asset->code,
            ] : null,
            'tenant_profile' => [
                'id' => $request->tenantProfile?->id,
                'user' => [
                    'name' => $request->tenantProfile?->user?->name,
                    'email' => $request->tenantProfile?->user?->email,
                ],
            ],
            'expense_total' => $publicOnly ? 0 : (float) $request->expenses->where('status', 'posted')->sum('amount'),
            'expense_count' => $publicOnly ? 0 : $request->expenses->count(),
            'updates' => $updates,
        ];
    }

    /**
     * @param  Builder<MaintenanceRequest>  $baseQuery
     * @return array<string, int|float>
     */
    private function insights(Builder $baseQuery, bool $includeFinancials): array
    {
        $requests = (clone $baseQuery)
            ->when($includeFinancials, fn ($query) => $query->with('expenses'))
            ->get();
        $active = $requests->whereIn('status', ['open', 'in_progress']);

        return [
            'total' => $requests->count(),
            'open' => $requests->where('status', 'open')->count(),
            'in_progress' => $requests->where('status', 'in_progress')->count(),
            'resolved' => $requests->where('status', 'resolved')->count(),
            'cancelled' => $requests->where('status', 'cancelled')->count(),
            'urgent' => $active->where('priority', 'urgent')->count(),
            'overdue' => $active
                ->filter(fn (MaintenanceRequest $request) => $request->due_at?->isPast() ?? false)
                ->count(),
            'unassigned' => $active->whereNull('assigned_to_user_id')->count(),
            'posted_expenses' => $includeFinancials
                ? (float) $requests->sum(fn (MaintenanceRequest $request) => $request->expenses
                    ->where('status', 'posted')
                    ->sum('amount'))
                : 0,
        ];
    }
}
