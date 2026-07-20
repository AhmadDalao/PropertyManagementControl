<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\MaintenanceRequest;
use App\Models\MaintenanceUpdate;
use App\Models\TenantProfile;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class MaintenanceRequestController extends Controller
{
    /**
     * @var array<int, string>
     */
    private array $categories = ['electricity', 'plumbing', 'ac', 'general'];

    /**
     * @var array<int, string>
     */
    private array $priorities = ['low', 'medium', 'high', 'urgent'];

    /**
     * @var array<int, string>
     */
    private array $statuses = ['open', 'in_progress', 'resolved', 'cancelled'];

    public function index(Request $request): Response
    {
        $actor = $this->actor($request);
        $filters = $this->tableFilters($request, [
            'status' => 'all',
            'category' => 'all',
            'priority' => 'all',
            'date_from' => '',
            'date_to' => '',
        ]);

        if ($actor->hasRole('tenant')) {
            $tenantProfile = TenantProfile::query()
                ->where('user_id', $actor->id)
                ->with(['leases.leaseable'])
                ->first();
            $baseQuery = MaintenanceRequest::query()
                ->when(
                    $tenantProfile,
                    fn ($query) => $query->where('tenant_profile_id', $tenantProfile->id),
                    fn ($query) => $query->whereRaw('1 = 0')
                );
            $requests = (clone $baseQuery)->with(['asset', 'tenantProfile.user', 'assignedTo', 'updates.user', 'expenses']);

            $this->applyExactFilter($requests, $filters, 'status');
            $this->applyExactFilter($requests, $filters, 'category');
            $this->applyExactFilter($requests, $filters, 'priority');
            $this->applyDateRange($requests, $filters, 'created_at');
            $this->applySearch($requests, $filters['search'], [
                'title',
                'description',
                'category',
                fn ($query, $search, $like) => $query->orWhereHas(
                    'asset',
                    fn ($assetQuery) => $assetQuery->where('title_en', 'like', $like)->orWhere('code', 'like', $like)
                ),
            ]);

            $paginatedRequests = $this->paginateTable($requests, $request, $filters, [
                'created_at',
                'requested_at',
                'status',
                'priority',
                'category',
            ])->through(fn (MaintenanceRequest $maintenanceRequest) => $this->maintenanceTableRow($maintenanceRequest, true));

            return Inertia::render('admin/maintenance/index', [
                'mode' => 'tenant',
                'requests' => $paginatedRequests,
                'maintenanceInsights' => $this->maintenanceInsights($baseQuery),
                'filters' => $filters,
                'counts' => $this->statusCounts($baseQuery, ['open', 'in_progress', 'resolved', 'cancelled'], $filters),
                'categoryOptions' => $this->categories,
                'priorityOptions' => $this->priorities,
                'statusOptions' => $this->statuses,
                'assetOptions' => $tenantProfile?->leases->map(fn ($lease) => $lease->leaseable)->filter()->values() ?? [],
                'tenantProfile' => $tenantProfile,
            ]);
        }

        $this->requireRoles($actor, ['superadmin', 'owner', 'property_manager']);
        $baseQuery = $this->scopeByPortfolio(MaintenanceRequest::query(), $actor);
        $requests = (clone $baseQuery)->with(['asset', 'tenantProfile.user', 'assignedTo', 'updates.user', 'expenses']);

        $this->applyExactFilter($requests, $filters, 'portfolio_id');
        $this->applyExactFilter($requests, $filters, 'status');
        $this->applyExactFilter($requests, $filters, 'category');
        $this->applyExactFilter($requests, $filters, 'priority');
        $this->applyDateRange($requests, $filters, 'created_at');
        $this->applySearch($requests, $filters['search'], [
            'title',
            'description',
            'category',
            'internal_notes',
            fn ($query, $search, $like) => $query->orWhereHas(
                'asset',
                fn ($assetQuery) => $assetQuery->where('title_en', 'like', $like)->orWhere('code', 'like', $like)
            ),
            fn ($query, $search, $like) => $query->orWhereHas(
                'tenantProfile.user',
                fn ($userQuery) => $userQuery->where('name', 'like', $like)->orWhere('email', 'like', $like)
            ),
            fn ($query, $search, $like) => $query->orWhereHas(
                'assignedTo',
                fn ($userQuery) => $userQuery->where('name', 'like', $like)->orWhere('email', 'like', $like)
            ),
        ]);

        $paginatedRequests = $this->paginateTable($requests, $request, $filters, [
            'created_at',
            'requested_at',
            'status',
            'priority',
            'category',
        ])->through(fn (MaintenanceRequest $maintenanceRequest) => $this->maintenanceTableRow($maintenanceRequest, false));

        return Inertia::render('admin/maintenance/index', [
            'mode' => 'manager',
            'requests' => $paginatedRequests,
            'maintenanceInsights' => $this->maintenanceInsights($baseQuery),
            'filters' => $filters,
            'counts' => $this->statusCounts($baseQuery, ['open', 'in_progress', 'resolved', 'cancelled'], $filters),
            'categoryOptions' => $this->categories,
            'priorityOptions' => $this->priorities,
            'statusOptions' => $this->statuses,
            'assetOptions' => $this->scopeByPortfolio(Asset::query(), $actor)->get(),
            'tenantOptions' => $this->scopeByPortfolio(TenantProfile::query()->with('user'), $actor)->get(),
            'userOptions' => $this->scopeByPortfolio(
                User::query()
                    ->whereHas('roles', fn ($query) => $query->whereIn('name', ['owner', 'property_manager']))
                    ->orderBy('name'),
                $actor
            )->get(['id', 'name', 'portfolio_id']),
        ]);
    }

    public function create(Request $request): Response
    {
        $actor = $this->actor($request);

        return Inertia::render('admin/resource-form', [
            'formPage' => $this->maintenanceFormPage($actor),
        ]);
    }

    public function show(Request $request, MaintenanceRequest $maintenanceRequest): Response
    {
        $actor = $this->actor($request);

        if ($actor->hasRole('tenant')) {
            abort_if($maintenanceRequest->tenantProfile?->user_id !== $actor->id, 403);
        } else {
            $this->requireRoles($actor, ['superadmin', 'owner', 'property_manager']);
            $this->ensurePortfolioAccess($actor, $maintenanceRequest->portfolio_id);
        }

        $maintenanceRequest->loadMissing([
            'portfolio',
            'asset',
            'lease',
            'tenantProfile.user',
            'submittedBy',
            'assignedTo',
            'updates.user',
            'expenses',
        ]);
        $adminMode = ! $actor->hasRole('tenant');

        return Inertia::render('admin/resource-show', [
            'detailPage' => [
                'header' => [
                    'eyebrow' => 'Maintenance detail',
                    'title' => '#'.$maintenanceRequest->id.' '.$maintenanceRequest->title,
                    'description' => trim($maintenanceRequest->category.' · '.$maintenanceRequest->priority.' · '.$maintenanceRequest->status),
                    'backHref' => route('maintenance-requests.index'),
                    'backLabel' => 'Maintenance queue',
                    'actions' => array_values(array_filter([
                        ['label' => $actor->hasRole('tenant') ? 'Add comment' : 'Triage request', 'href' => route('maintenance-requests.edit', $maintenanceRequest), 'variant' => 'primary'],
                        $adminMode ? ['label' => 'Open asset', 'href' => route('assets.show', $maintenanceRequest->asset), 'variant' => 'secondary'] : null,
                    ])),
                ],
                'stats' => $this->detailItems([
                    ['label' => 'Status', 'value' => $maintenanceRequest->status, 'tone' => $maintenanceRequest->status === 'resolved' ? 'teal' : 'primary'],
                    ['label' => 'Priority', 'value' => $maintenanceRequest->priority, 'tone' => in_array($maintenanceRequest->priority, ['high', 'urgent'], true) ? 'danger' : 'muted'],
                    ['label' => 'Updates', 'value' => $maintenanceRequest->updates->count()],
                    ['label' => 'Cost', 'value' => number_format((float) $maintenanceRequest->expenses->sum('amount'), 2), 'tone' => 'primary'],
                ]),
                'sections' => [
                    [
                        'title' => 'Request',
                        'description' => 'Problem, people, asset, and SLA context.',
                        'items' => $this->detailItems([
                            ['label' => 'Asset', 'value' => $this->localized($maintenanceRequest->asset?->title_en, $maintenanceRequest->asset?->title_ar), 'href' => $maintenanceRequest->asset ? route('assets.show', $maintenanceRequest->asset) : null],
                            ['label' => 'Tenant', 'value' => $maintenanceRequest->tenantProfile?->user?->name, 'href' => $maintenanceRequest->tenantProfile ? route('tenants.show', $maintenanceRequest->tenantProfile) : null],
                            ['label' => 'Lease', 'value' => $maintenanceRequest->lease?->code, 'href' => $maintenanceRequest->lease ? route('leases.show', $maintenanceRequest->lease) : null],
                            ['label' => 'Submitted by', 'value' => $maintenanceRequest->submittedBy?->name],
                            ['label' => 'Assigned to', 'value' => $maintenanceRequest->assignedTo?->name],
                            ['label' => 'Requested at', 'value' => $maintenanceRequest->requested_at?->toDateTimeString()],
                            ['label' => 'Due at', 'value' => $maintenanceRequest->due_at?->toDateTimeString()],
                            ['label' => 'Resolved at', 'value' => $maintenanceRequest->resolved_at?->toDateTimeString()],
                            ['label' => 'Description', 'value' => $maintenanceRequest->description],
                            ['label' => 'Internal notes', 'value' => $actor->hasRole('tenant') ? null : $maintenanceRequest->internal_notes],
                        ]),
                    ],
                ],
                'related' => [
                    [
                        'title' => 'Updates',
                        'description' => 'Public comments, internal notes, and status transitions.',
                        'columns' => ['By', 'From', 'To', 'Comment'],
                        'rows' => $maintenanceRequest->updates->map(fn (MaintenanceUpdate $update) => [
                            'By' => $update->user?->name ?? 'System',
                            'From' => $update->status_from ?? '-',
                            'To' => $update->status_to ?? '-',
                            'Comment' => $update->comment,
                        ])->all(),
                        'emptyText' => 'No updates yet.',
                    ],
                    [
                        'title' => 'Expenses',
                        'description' => 'Maintenance costs linked to this request.',
                        'columns' => ['Expense', 'Vendor', 'Status', 'Amount'],
                        'rows' => $maintenanceRequest->expenses->map(fn ($expense) => [
                            'Expense' => $expense->title,
                            'Vendor' => $expense->vendor_name ?? '-',
                            'Status' => $expense->status,
                            'Amount' => number_format((float) $expense->amount, 2).' '.$expense->currency,
                        ])->all(),
                        'emptyText' => 'No expenses linked yet.',
                        'actionHref' => $actor->hasRole('tenant') ? null : route('expenses.create', ['maintenance_request_id' => $maintenanceRequest->id]),
                        'actionLabel' => 'Add expense',
                    ],
                ],
                'documents' => [],
                'timeline' => $this->activityTimeline($maintenanceRequest),
            ],
        ]);
    }

    public function edit(Request $request, MaintenanceRequest $maintenanceRequest): Response
    {
        $actor = $this->actor($request);

        if ($actor->hasRole('tenant')) {
            abort_if($maintenanceRequest->tenantProfile?->user_id !== $actor->id, 403);
        } else {
            $this->requireRoles($actor, ['superadmin', 'owner', 'property_manager']);
            $this->ensurePortfolioAccess($actor, $maintenanceRequest->portfolio_id);
        }

        return Inertia::render('admin/resource-form', [
            'formPage' => $this->maintenanceFormPage($actor, $maintenanceRequest),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $actor = $this->actor($request);

        if ($actor->hasRole('tenant')) {
            $tenantProfile = TenantProfile::query()->where('user_id', $actor->id)->firstOrFail();

            $data = $request->validate([
                'asset_id' => ['required', 'integer', 'exists:assets,id'],
                'category' => ['required', Rule::in($this->categories)],
                'priority' => ['required', Rule::in($this->priorities)],
                'title' => ['required', 'string', 'max:255'],
                'description' => ['required', 'string'],
            ]);

            $lease = $tenantProfile->leases()
                ->where('status', 'active')
                ->where('leaseable_type', Asset::class)
                ->where('leaseable_id', $data['asset_id'])
                ->first();

            abort_unless(
                $lease,
                422,
                trans('app.errors.rented_asset_only')
            );

            $requestItem = MaintenanceRequest::query()->create([
                'portfolio_id' => $tenantProfile->portfolio_id,
                'asset_id' => $data['asset_id'],
                'lease_id' => $lease->id,
                'tenant_profile_id' => $tenantProfile->id,
                'submitted_by_user_id' => $actor->id,
                'category' => $data['category'],
                'priority' => $data['priority'],
                'status' => 'open',
                'title' => $data['title'],
                'description' => $data['description'],
                'requested_at' => now(),
                'due_at' => $this->dueAtForPriority($data['priority']),
            ]);

            $requestItem->updates()->create([
                'user_id' => $actor->id,
                'status_to' => 'open',
                'is_public_comment' => true,
                'comment' => 'Maintenance request created by tenant.',
            ]);

            return to_route('maintenance-requests.show', $requestItem)->with('success', trans('app.messages.maintenance_submitted'));
        }

        $this->requireRoles($actor, ['superadmin', 'owner', 'property_manager']);

        $data = $request->validate([
            'portfolio_id' => ['nullable', 'integer', 'exists:portfolios,id'],
            'asset_id' => ['required', 'integer', 'exists:assets,id'],
            'tenant_profile_id' => ['required', 'integer', 'exists:tenant_profiles,id'],
            'assigned_to_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'category' => ['required', Rule::in($this->categories)],
            'priority' => ['required', Rule::in($this->priorities)],
            'status' => ['required', Rule::in($this->statuses)],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'internal_notes' => ['nullable', 'string'],
        ]);

        $portfolioId = $data['portfolio_id'] ?? $actor->portfolio_id;
        $this->ensurePortfolioAccess($actor, $portfolioId);
        $this->ensureMaintenanceReferencesBelongToPortfolio($data, $portfolioId);

        $requestItem = MaintenanceRequest::query()->create([
            'portfolio_id' => $portfolioId,
            'asset_id' => $data['asset_id'],
            'tenant_profile_id' => $data['tenant_profile_id'],
            'submitted_by_user_id' => $actor->id,
            'assigned_to_user_id' => $data['assigned_to_user_id'] ?? null,
            'category' => $data['category'],
            'priority' => $data['priority'],
            'status' => $data['status'],
            'title' => $data['title'],
            'description' => $data['description'],
            'internal_notes' => $data['internal_notes'] ?? null,
            'requested_at' => now(),
            'due_at' => $this->dueAtForPriority($data['priority']),
            'resolved_at' => $data['status'] === 'resolved' ? now() : null,
        ]);

        $requestItem->updates()->create([
            'user_id' => $actor->id,
            'status_to' => $data['status'],
            'is_public_comment' => false,
            'comment' => 'Maintenance request created by management.',
        ]);

        return to_route('maintenance-requests.show', $requestItem)->with('success', trans('app.messages.maintenance_created'));
    }

    public function update(Request $request, MaintenanceRequest $maintenanceRequest): RedirectResponse
    {
        $actor = $this->actor($request);

        if ($actor->hasRole('tenant')) {
            abort_if($maintenanceRequest->tenantProfile?->user_id !== $actor->id, 403);

            $data = $request->validate([
                'comment' => ['required', 'string'],
            ]);

            $maintenanceRequest->updates()->create([
                'user_id' => $actor->id,
                'status_from' => $maintenanceRequest->status,
                'status_to' => $maintenanceRequest->status,
                'is_public_comment' => true,
                'comment' => $data['comment'],
            ]);

            return to_route('maintenance-requests.show', $maintenanceRequest)->with('success', trans('app.messages.maintenance_comment_added'));
        }

        $this->requireRoles($actor, ['superadmin', 'owner', 'property_manager']);
        $this->ensurePortfolioAccess($actor, $maintenanceRequest->portfolio_id);

        $data = $request->validate([
            'assigned_to_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'priority' => ['required', Rule::in($this->priorities)],
            'status' => ['required', Rule::in($this->statuses)],
            'internal_notes' => ['nullable', 'string'],
            'comment' => ['nullable', 'string'],
            'is_public_comment' => ['nullable', 'boolean'],
        ]);

        $this->ensureMaintenanceReferencesBelongToPortfolio($data, $maintenanceRequest->portfolio_id);

        $previousStatus = $maintenanceRequest->status;
        $previousPriority = $maintenanceRequest->priority;
        $previousAssignee = $maintenanceRequest->assigned_to_user_id;
        $maintenanceRequest->update([
            'assigned_to_user_id' => $data['assigned_to_user_id'] ?? null,
            'priority' => $data['priority'],
            'status' => $data['status'],
            'internal_notes' => $data['internal_notes'] ?? null,
            'due_at' => $this->nextDueAt($maintenanceRequest, $data['priority'], $data['status'], $previousPriority),
            'resolved_at' => $data['status'] === 'resolved' ? now() : null,
        ]);

        if (
            ! empty($data['comment'])
            || $previousStatus !== $maintenanceRequest->status
            || $previousPriority !== $maintenanceRequest->priority
            || (int) $previousAssignee !== (int) $maintenanceRequest->assigned_to_user_id
        ) {
            $maintenanceRequest->updates()->create([
                'user_id' => $actor->id,
                'status_from' => $previousStatus,
                'status_to' => $maintenanceRequest->status,
                'is_public_comment' => (bool) ($data['is_public_comment'] ?? false),
                'comment' => $data['comment'] ?? 'Maintenance request updated.',
            ]);
        }

        return to_route('maintenance-requests.show', $maintenanceRequest)->with('success', trans('app.messages.maintenance_updated'));
    }

    public function destroy(Request $request, MaintenanceRequest $maintenanceRequest): RedirectResponse
    {
        $actor = $this->actor($request);

        if ($actor->hasRole('tenant')) {
            abort_if($maintenanceRequest->tenantProfile?->user_id !== $actor->id, 403);

            if (! in_array($maintenanceRequest->status, ['open', 'in_progress'], true)) {
                return back()->with('error', trans('app.errors.maintenance_not_open'));
            }

            $previousStatus = $maintenanceRequest->status;
            $maintenanceRequest->update(['status' => 'cancelled']);
            $maintenanceRequest->updates()->create([
                'user_id' => $actor->id,
                'status_from' => $previousStatus,
                'status_to' => 'cancelled',
                'is_public_comment' => true,
                'comment' => 'Maintenance request cancelled by tenant.',
            ]);

            return to_route('maintenance-requests.index')->with('success', trans('app.messages.maintenance_cancelled'));
        }

        $this->requireRoles($actor, ['superadmin', 'owner', 'property_manager']);
        $this->ensurePortfolioAccess($actor, $maintenanceRequest->portfolio_id);

        $previousStatus = $maintenanceRequest->status;
        $maintenanceRequest->update(['status' => 'cancelled']);
        $maintenanceRequest->updates()->create([
            'user_id' => $actor->id,
            'status_from' => $previousStatus,
            'status_to' => 'cancelled',
            'is_public_comment' => false,
            'comment' => 'Maintenance request cancelled by management.',
        ]);

        return to_route('maintenance-requests.index')->with('success', trans('app.messages.maintenance_cancelled'));
    }

    private function maintenanceFormPage(User $actor, ?MaintenanceRequest $maintenanceRequest = null): array
    {
        if ($maintenanceRequest) {
            if ($actor->hasRole('tenant')) {
                return [
                    'title' => 'Add maintenance comment',
                    'description' => 'Send a public update to the owner or manager.',
                    'backHref' => route('maintenance-requests.show', $maintenanceRequest),
                    'backLabel' => 'Request detail',
                    'action' => route('maintenance-requests.update', $maintenanceRequest),
                    'method' => 'put',
                    'submitLabel' => 'Add comment',
                    'fields' => [
                        ['name' => 'comment', 'label' => 'Comment', 'type' => 'textarea', 'required' => true],
                    ],
                    'initialValues' => [
                        'comment' => '',
                    ],
                ];
            }

            $userOptions = $this->scopeByPortfolio(
                User::query()->whereHas('roles', fn ($query) => $query->whereIn('name', ['owner', 'property_manager']))->orderBy('name'),
                $actor
            )->get()->map(fn (User $user) => ['value' => $user->id, 'label' => $user->name])
                ->prepend(['value' => '', 'label' => 'Unassigned'])
                ->values()
                ->all();

            return [
                'title' => 'Triage request #'.$maintenanceRequest->id,
                'description' => 'Assign, prioritize, change status, and leave a visible or internal update.',
                'backHref' => route('maintenance-requests.show', $maintenanceRequest),
                'backLabel' => 'Request detail',
                'action' => route('maintenance-requests.update', $maintenanceRequest),
                'method' => 'put',
                'submitLabel' => 'Update request',
                'fields' => [
                    ['name' => 'assigned_to_user_id', 'label' => 'Assignee', 'type' => 'select', 'options' => $userOptions],
                    ['name' => 'priority', 'label' => 'Priority', 'type' => 'select', 'options' => $this->fieldOptions($this->priorities)],
                    ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => $this->fieldOptions($this->statuses)],
                    ['name' => 'internal_notes', 'label' => 'Internal notes', 'type' => 'textarea'],
                    ['name' => 'comment', 'label' => 'Update comment', 'type' => 'textarea'],
                    ['name' => 'is_public_comment', 'label' => 'Show comment to tenant', 'type' => 'checkbox'],
                ],
                'initialValues' => [
                    'assigned_to_user_id' => (string) ($maintenanceRequest->assigned_to_user_id ?? ''),
                    'priority' => $maintenanceRequest->priority,
                    'status' => $maintenanceRequest->status,
                    'internal_notes' => $maintenanceRequest->internal_notes ?? '',
                    'comment' => '',
                    'is_public_comment' => false,
                ],
            ];
        }

        if ($actor->hasRole('tenant')) {
            $tenantProfile = TenantProfile::query()
                ->where('user_id', $actor->id)
                ->with(['leases' => fn ($query) => $query->where('status', 'active')->with('leaseable')])
                ->first();
            $assetOptions = $tenantProfile?->leases
                ->map(fn ($lease) => $lease->leaseable)
                ->filter()
                ->map(fn (Asset $asset) => [
                    'value' => $asset->id,
                    'label' => $this->localized($asset->title_en, $asset->title_ar).' · '.$asset->code,
                ])
                ->values()
                ->all() ?? [];

            return [
                'title' => 'Submit maintenance request',
                'description' => 'Tell the owner what broke and where. Keep it clear; photos can be added later through documents.',
                'backHref' => route('maintenance-requests.index'),
                'backLabel' => 'My requests',
                'action' => route('maintenance-requests.store'),
                'method' => 'post',
                'submitLabel' => 'Submit request',
                'fields' => [
                    ['name' => 'asset_id', 'label' => 'Rented asset', 'type' => 'select', 'required' => true, 'options' => $assetOptions],
                    ['name' => 'category', 'label' => 'Category', 'type' => 'select', 'options' => $this->fieldOptions($this->categories)],
                    ['name' => 'priority', 'label' => 'Priority', 'type' => 'select', 'options' => $this->fieldOptions($this->priorities)],
                    ['name' => 'title', 'label' => 'Issue title', 'required' => true],
                    ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'required' => true],
                ],
                'initialValues' => [
                    'asset_id' => (string) request('asset_id', $assetOptions[0]['value'] ?? ''),
                    'category' => 'general',
                    'priority' => 'medium',
                    'title' => '',
                    'description' => '',
                ],
            ];
        }

        $this->requireRoles($actor, ['superadmin', 'owner', 'property_manager']);
        $assetOptions = $this->scopeByPortfolio(Asset::query()->orderBy('title_en'), $actor)
            ->get()
            ->map(fn (Asset $asset) => [
                'value' => $asset->id,
                'label' => $this->localized($asset->title_en, $asset->title_ar).' · '.$asset->code,
            ])
            ->all();
        $tenantOptions = $this->scopeByPortfolio(TenantProfile::query()->with('user'), $actor)
            ->get()
            ->map(fn (TenantProfile $tenant) => ['value' => $tenant->id, 'label' => $tenant->user?->name ?? 'Tenant #'.$tenant->id])
            ->all();
        $userOptions = $this->scopeByPortfolio(
            User::query()->whereHas('roles', fn ($query) => $query->whereIn('name', ['owner', 'property_manager']))->orderBy('name'),
            $actor
        )->get()->map(fn (User $user) => ['value' => $user->id, 'label' => $user->name])
            ->prepend(['value' => '', 'label' => 'Unassigned'])
            ->values()
            ->all();
        $fields = [];

        if ($actor->hasRole('superadmin')) {
            $fields[] = [
                'name' => 'portfolio_id',
                'label' => 'Portfolio',
                'type' => 'select',
                'options' => collect($this->portfolioOptions($actor))->map(fn ($portfolio) => ['value' => $portfolio['id'], 'label' => $portfolio['name']])->all(),
            ];
        }

        $fields = [
            ...$fields,
            ['name' => 'asset_id', 'label' => 'Asset', 'type' => 'select', 'required' => true, 'options' => $assetOptions],
            ['name' => 'tenant_profile_id', 'label' => 'Tenant', 'type' => 'select', 'required' => true, 'options' => $tenantOptions],
            ['name' => 'assigned_to_user_id', 'label' => 'Assignee', 'type' => 'select', 'options' => $userOptions],
            ['name' => 'category', 'label' => 'Category', 'type' => 'select', 'options' => $this->fieldOptions($this->categories)],
            ['name' => 'priority', 'label' => 'Priority', 'type' => 'select', 'options' => $this->fieldOptions($this->priorities)],
            ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => $this->fieldOptions($this->statuses)],
            ['name' => 'title', 'label' => 'Issue title', 'required' => true],
            ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'required' => true],
            ['name' => 'internal_notes', 'label' => 'Internal notes', 'type' => 'textarea'],
        ];

        return [
            'title' => 'Create maintenance request',
            'description' => 'Open an issue, attach it to the right tenant and asset, then triage from the detail page.',
            'backHref' => route('maintenance-requests.index'),
            'backLabel' => 'Maintenance queue',
            'action' => route('maintenance-requests.store'),
            'method' => 'post',
            'submitLabel' => 'Create request',
            'fields' => $fields,
            'initialValues' => [
                'portfolio_id' => (string) request('portfolio_id', $actor->portfolio_id ?? $this->portfolioOptions($actor)[0]['id'] ?? ''),
                'asset_id' => (string) request('asset_id', $assetOptions[0]['value'] ?? ''),
                'tenant_profile_id' => (string) request('tenant_profile_id', $tenantOptions[0]['value'] ?? ''),
                'assigned_to_user_id' => '',
                'category' => 'general',
                'priority' => 'medium',
                'status' => 'open',
                'title' => '',
                'description' => '',
                'internal_notes' => '',
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function ensureMaintenanceReferencesBelongToPortfolio(array $data, int $portfolioId): void
    {
        if (! empty($data['asset_id'])) {
            abort_unless(
                Asset::query()->whereKey($data['asset_id'])->where('portfolio_id', $portfolioId)->exists(),
                422,
                trans('app.errors.asset_portfolio_mismatch')
            );
        }

        if (! empty($data['tenant_profile_id'])) {
            abort_unless(
                TenantProfile::query()
                    ->whereKey($data['tenant_profile_id'])
                    ->where('portfolio_id', $portfolioId)
                    ->exists(),
                422,
                trans('app.errors.tenant_selection_portfolio_mismatch')
            );
        }

        if (! empty($data['assigned_to_user_id'])) {
            abort_unless(
                User::query()
                    ->whereKey($data['assigned_to_user_id'])
                    ->where('portfolio_id', $portfolioId)
                    ->whereHas('roles', fn ($query) => $query->whereIn('name', ['owner', 'property_manager']))
                    ->exists(),
                422,
                trans('app.errors.manager_assignment_invalid')
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function maintenanceTableRow(MaintenanceRequest $maintenanceRequest, bool $publicOnly): array
    {
        $maintenanceRequest->loadMissing(['asset', 'tenantProfile.user', 'assignedTo', 'updates.user', 'expenses']);

        $updates = $maintenanceRequest->updates
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

        $expenses = $maintenanceRequest->expenses;

        return [
            'id' => $maintenanceRequest->id,
            'title' => $maintenanceRequest->title,
            'description' => $maintenanceRequest->description,
            'status' => $maintenanceRequest->status,
            'category' => $maintenanceRequest->category,
            'priority' => $maintenanceRequest->priority,
            'created_at' => $maintenanceRequest->created_at?->toIso8601String(),
            'requested_at' => $maintenanceRequest->requested_at?->toIso8601String(),
            'due_at' => $maintenanceRequest->due_at?->toIso8601String(),
            'resolved_at' => $maintenanceRequest->resolved_at?->toIso8601String(),
            'is_overdue' => $maintenanceRequest->due_at
                ? $maintenanceRequest->due_at->isPast() && ! in_array($maintenanceRequest->status, ['resolved', 'cancelled'], true)
                : false,
            'assigned_to_user_id' => $maintenanceRequest->assigned_to_user_id,
            'assigned_to' => $maintenanceRequest->assignedTo ? [
                'id' => $maintenanceRequest->assignedTo->id,
                'name' => $maintenanceRequest->assignedTo->name,
            ] : null,
            'internal_notes' => $publicOnly ? null : $maintenanceRequest->internal_notes,
            'asset' => $maintenanceRequest->asset ? [
                'id' => $maintenanceRequest->asset->id,
                'title_en' => $maintenanceRequest->asset->title_en,
                'title_ar' => $maintenanceRequest->asset->title_ar,
                'code' => $maintenanceRequest->asset->code,
            ] : null,
            'tenant_profile' => [
                'id' => $maintenanceRequest->tenantProfile?->id,
                'user' => [
                    'name' => $maintenanceRequest->tenantProfile?->user?->name,
                    'email' => $maintenanceRequest->tenantProfile?->user?->email,
                ],
            ],
            'expense_total' => (float) $expenses->where('status', 'posted')->sum('amount'),
            'expense_count' => $expenses->count(),
            'updates' => $updates,
        ];
    }

    /**
     * @return array<string, int|float>
     */
    private function maintenanceInsights(Builder $baseQuery): array
    {
        $requests = (clone $baseQuery)->with('expenses')->get();
        $active = $requests->whereIn('status', ['open', 'in_progress']);

        return [
            'total' => $requests->count(),
            'open' => $requests->where('status', 'open')->count(),
            'in_progress' => $requests->where('status', 'in_progress')->count(),
            'resolved' => $requests->where('status', 'resolved')->count(),
            'cancelled' => $requests->where('status', 'cancelled')->count(),
            'urgent' => $active->where('priority', 'urgent')->count(),
            'overdue' => $active
                ->filter(fn (MaintenanceRequest $maintenanceRequest) => $maintenanceRequest->due_at?->isPast() ?? false)
                ->count(),
            'unassigned' => $active->whereNull('assigned_to_user_id')->count(),
            'posted_expenses' => (float) $requests->sum(fn (MaintenanceRequest $maintenanceRequest) => $maintenanceRequest->expenses
                ->where('status', 'posted')
                ->sum('amount')),
        ];
    }

    private function nextDueAt(
        MaintenanceRequest $maintenanceRequest,
        string $priority,
        string $status,
        string $previousPriority
    ): ?CarbonInterface {
        if ($status === 'resolved' || $status === 'cancelled') {
            return $maintenanceRequest->due_at;
        }

        if ($priority !== $previousPriority || ! $maintenanceRequest->due_at) {
            return $this->dueAtForPriority($priority);
        }

        return $maintenanceRequest->due_at;
    }

    private function dueAtForPriority(string $priority): CarbonInterface
    {
        return match ($priority) {
            'urgent' => now()->addHours(24),
            'high' => now()->addDays(2),
            'low' => now()->addDays(7),
            default => now()->addDays(4),
        };
    }
}
