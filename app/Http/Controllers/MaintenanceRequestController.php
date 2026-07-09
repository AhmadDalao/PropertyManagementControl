<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\MaintenanceRequest;
use App\Models\TenantProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MaintenanceRequestController extends Controller
{
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
            $requests = (clone $baseQuery)->with(['asset', 'tenantProfile.user', 'assignedTo', 'updates.user']);

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

            return Inertia::render('admin/maintenance/index', [
                'mode' => 'tenant',
                'requests' => $this->paginateTable($requests, $request, $filters, [
                    'created_at',
                    'requested_at',
                    'status',
                    'priority',
                    'category',
                ]),
                'filters' => $filters,
                'counts' => $this->statusCounts($baseQuery, ['open', 'in_progress', 'resolved', 'cancelled'], $filters),
                'assetOptions' => $tenantProfile?->leases->map(fn ($lease) => $lease->leaseable)->filter()->values() ?? [],
                'tenantProfile' => $tenantProfile,
            ]);
        }

        $this->requireRoles($actor, ['superadmin', 'owner', 'property_manager']);
        $baseQuery = $this->scopeByPortfolio(MaintenanceRequest::query(), $actor);
        $requests = (clone $baseQuery)->with(['asset', 'tenantProfile.user', 'assignedTo', 'updates.user']);

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

        return Inertia::render('admin/maintenance/index', [
            'mode' => 'manager',
            'requests' => $this->paginateTable($requests, $request, $filters, [
                'created_at',
                'requested_at',
                'status',
                'priority',
                'category',
            ]),
            'filters' => $filters,
            'counts' => $this->statusCounts($baseQuery, ['open', 'in_progress', 'resolved', 'cancelled'], $filters),
            'assetOptions' => $this->scopeByPortfolio(Asset::query(), $actor)->get(),
            'tenantOptions' => $this->scopeByPortfolio(TenantProfile::query()->with('user'), $actor)->get(),
            'userOptions' => $this->scopeByPortfolio(User::query()->orderBy('name'), $actor)->get(['id', 'name', 'portfolio_id']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $actor = $this->actor($request);

        if ($actor->hasRole('tenant')) {
            $tenantProfile = TenantProfile::query()->where('user_id', $actor->id)->firstOrFail();

            $data = $request->validate([
                'asset_id' => ['required', 'integer', 'exists:assets,id'],
                'category' => ['required', 'string'],
                'priority' => ['required', 'string'],
                'title' => ['required', 'string', 'max:255'],
                'description' => ['required', 'string'],
            ]);

            $requestItem = MaintenanceRequest::query()->create([
                'portfolio_id' => $tenantProfile->portfolio_id,
                'asset_id' => $data['asset_id'],
                'lease_id' => $tenantProfile->leases()->where('status', 'active')->value('id'),
                'tenant_profile_id' => $tenantProfile->id,
                'submitted_by_user_id' => $actor->id,
                'category' => $data['category'],
                'priority' => $data['priority'],
                'status' => 'open',
                'title' => $data['title'],
                'description' => $data['description'],
                'requested_at' => now(),
            ]);

            $requestItem->updates()->create([
                'user_id' => $actor->id,
                'status_to' => 'open',
                'is_public_comment' => true,
                'comment' => 'Maintenance request created by tenant.',
            ]);

            return to_route('maintenance-requests.index')->with('success', 'Maintenance request submitted.');
        }

        $this->requireRoles($actor, ['superadmin', 'owner', 'property_manager']);

        $data = $request->validate([
            'portfolio_id' => ['nullable', 'integer', 'exists:portfolios,id'],
            'asset_id' => ['required', 'integer', 'exists:assets,id'],
            'tenant_profile_id' => ['required', 'integer', 'exists:tenant_profiles,id'],
            'assigned_to_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'category' => ['required', 'string'],
            'priority' => ['required', 'string'],
            'status' => ['required', 'string'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'internal_notes' => ['nullable', 'string'],
        ]);

        $portfolioId = $data['portfolio_id'] ?? $actor->portfolio_id;
        $this->ensurePortfolioAccess($actor, $portfolioId);

        MaintenanceRequest::query()->create([
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
        ]);

        return to_route('maintenance-requests.index')->with('success', 'Maintenance request created.');
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

            return to_route('maintenance-requests.index')->with('success', 'Comment added to maintenance request.');
        }

        $this->requireRoles($actor, ['superadmin', 'owner', 'property_manager']);
        $this->ensurePortfolioAccess($actor, $maintenanceRequest->portfolio_id);

        $data = $request->validate([
            'assigned_to_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'priority' => ['required', 'string'],
            'status' => ['required', 'string'],
            'internal_notes' => ['nullable', 'string'],
            'comment' => ['nullable', 'string'],
        ]);

        $previousStatus = $maintenanceRequest->status;
        $maintenanceRequest->update([
            'assigned_to_user_id' => $data['assigned_to_user_id'] ?? null,
            'priority' => $data['priority'],
            'status' => $data['status'],
            'internal_notes' => $data['internal_notes'] ?? null,
            'resolved_at' => $data['status'] === 'resolved' ? now() : null,
        ]);

        if (! empty($data['comment']) || $previousStatus !== $maintenanceRequest->status) {
            $maintenanceRequest->updates()->create([
                'user_id' => $actor->id,
                'status_from' => $previousStatus,
                'status_to' => $maintenanceRequest->status,
                'is_public_comment' => false,
                'comment' => $data['comment'] ?? 'Maintenance request updated.',
            ]);
        }

        return to_route('maintenance-requests.index')->with('success', 'Maintenance request updated.');
    }
}
