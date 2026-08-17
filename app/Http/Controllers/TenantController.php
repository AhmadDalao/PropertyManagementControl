<?php

namespace App\Http\Controllers;

use App\Models\TenantProfile;
use App\Modules\Tenants\Actions\ManageTenants;
use App\Modules\Tenants\Presenters\TenantDetailPresenter;
use App\Modules\Tenants\Presenters\TenantFormPresenter;
use App\Modules\Tenants\Queries\TenantIndexQuery;
use App\Modules\Tenants\Requests\StoreTenantRequest;
use App\Modules\Tenants\Requests\UpdateTenantRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TenantController extends Controller
{
    public function __construct(
        private readonly TenantIndexQuery $indexQuery,
        private readonly TenantFormPresenter $formPresenter,
        private readonly TenantDetailPresenter $detailPresenter,
        private readonly ManageTenants $tenants,
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render(
            'admin/tenants/index',
            $this->indexQuery->handle($request, $this->actor($request)),
        );
    }

    public function create(Request $request): Response
    {
        return Inertia::render('admin/resource-form', [
            'formPage' => $this->formPresenter->present(
                $this->actor($request),
                defaults: $request->only(['portfolio_id', 'asset_id', 'next']),
            ),
        ]);
    }

    public function show(Request $request, TenantProfile $tenant): Response
    {
        return Inertia::render('admin/tenants/show', [
            'detailPage' => $this->detailPresenter->present($tenant, $this->actor($request)),
        ]);
    }

    public function edit(Request $request, TenantProfile $tenant): Response
    {
        return Inertia::render('admin/resource-form', [
            'formPage' => $this->formPresenter->present($this->actor($request), $tenant),
        ]);
    }

    public function store(StoreTenantRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $next = $data['next'] ?? null;
        $assetId = $data['asset_id'] ?? null;
        unset($data['next'], $data['asset_id']);
        $tenant = $this->tenants->create($this->actor($request), $data);

        if ($next === 'lease') {
            $query = [
                'tenant_profile_id' => $tenant->id,
                'onboarding' => 1,
            ];

            if ($assetId) {
                $query['asset_id'] = $assetId;
            }

            return to_route('leases.create', $query)
                ->with('success', trans('app.messages.tenant_created_continue_lease'));
        }

        return to_route('tenants.show', $tenant)
            ->with('success', trans('app.messages.tenant_created'));
    }

    public function update(UpdateTenantRequest $request, TenantProfile $tenant): RedirectResponse
    {
        $this->tenants->update($this->actor($request), $tenant, $request->validated());

        return to_route('tenants.show', $tenant)
            ->with('success', trans('app.messages.tenant_updated'));
    }

    public function destroy(Request $request, TenantProfile $tenant): RedirectResponse
    {
        if (! $this->tenants->archive($this->actor($request), $tenant)) {
            return back()->with('error', trans('app.errors.tenant_has_active_lease'));
        }

        return to_route('tenants.index')
            ->with('success', trans('app.messages.tenant_archived'));
    }
}
