<?php

namespace App\Http\Controllers;

use App\Models\TenantProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class TenantController extends Controller
{
    public function index(Request $request): Response
    {
        $actor = $this->actor($request);
        $this->requireRoles($actor, ['superadmin', 'owner', 'property_manager']);

        $filters = $this->tableFilters($request, [
            'status' => 'all',
            'profile_type' => 'all',
        ]);
        $baseQuery = $this->scopeByPortfolio(TenantProfile::query(), $actor);
        $tenants = (clone $baseQuery)
            ->with(['user', 'leases' => fn ($query) => $query->latest('started_at')])
            ->withCount([
                'leases',
                'leases as active_leases_count' => fn (Builder $query) => $query->where('status', 'active'),
                'maintenanceRequests as open_requests_count' => fn (Builder $query) => $query->whereIn('status', ['open', 'in_progress']),
            ]);

        $this->applyExactFilter($tenants, $filters, 'portfolio_id');
        $this->applyExactFilter($tenants, $filters, 'status');
        $this->applyExactFilter($tenants, $filters, 'profile_type');
        $this->applySearch($tenants, $filters['search'], [
            'national_id',
            'company_name',
            'emergency_contact_name',
            'emergency_contact_phone',
            'address',
            fn ($query, $search, $like) => $query->orWhereHas(
                'user',
                fn ($userQuery) => $userQuery
                    ->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('phone', 'like', $like)
            ),
        ]);

        return Inertia::render('admin/tenants/index', [
            'tenants' => $this->paginateTable($tenants, $request, $filters, [
                'created_at',
                'status',
                'profile_type',
                'company_name',
            ]),
            'filters' => $filters,
            'counts' => $this->statusCounts($baseQuery, ['active', 'inactive', 'blocked'], $filters),
            'portfolioOptions' => $this->portfolioOptions($actor),
            'tenantInsights' => $this->tenantInsights($baseQuery),
        ]);
    }

    public function create(Request $request): Response
    {
        $actor = $this->actor($request);
        $this->requireRoles($actor, ['superadmin', 'owner', 'property_manager']);

        return Inertia::render('admin/resource-form', [
            'formPage' => $this->tenantFormPage($actor),
        ]);
    }

    public function show(Request $request, TenantProfile $tenant): Response
    {
        $actor = $this->actor($request);
        $this->requireRoles($actor, ['superadmin', 'owner', 'property_manager']);
        $this->ensurePortfolioAccess($actor, $tenant->portfolio_id);

        $tenant->loadMissing([
            'portfolio',
            'user',
            'leases.leaseable',
            'leases.installments',
            'leases.documents',
            'payments.lease',
            'maintenanceRequests.asset',
        ]);

        $activeLease = $tenant->leases->firstWhere('status', 'active');

        return Inertia::render('admin/resource-show', [
            'detailPage' => [
                'header' => [
                    'eyebrow' => 'Tenant detail',
                    'title' => $tenant->user?->name ?? $tenant->company_name ?? 'Tenant #'.$tenant->id,
                    'description' => trim(($tenant->user?->email ?? '').' · '.$tenant->profile_type.' · '.$tenant->status),
                    'backHref' => route('tenants.index'),
                    'backLabel' => 'All tenants',
                    'actions' => [
                        ['label' => 'Edit tenant', 'href' => route('tenants.edit', $tenant), 'variant' => 'primary'],
                        ['label' => 'Create lease', 'href' => route('leases.create', ['tenant_profile_id' => $tenant->id]), 'variant' => 'secondary'],
                        ['label' => 'Record payment', 'href' => route('payments.create', ['tenant_profile_id' => $tenant->id]), 'variant' => 'secondary'],
                    ],
                ],
                'stats' => $this->detailItems([
                    ['label' => 'Status', 'value' => $tenant->status, 'tone' => $tenant->status === 'active' ? 'teal' : 'muted'],
                    ['label' => 'Active leases', 'value' => $tenant->leases->where('status', 'active')->count(), 'tone' => 'primary'],
                    ['label' => 'Paid', 'value' => number_format((float) $tenant->payments->where('status', 'posted')->sum('amount'), 2)],
                    ['label' => 'Open maintenance', 'value' => $tenant->maintenanceRequests->whereIn('status', ['open', 'in_progress'])->count(), 'tone' => 'danger'],
                ]),
                'sections' => [
                    [
                        'title' => 'Profile',
                        'description' => 'Identity, contact, emergency, and account state.',
                        'items' => $this->detailItems([
                            ['label' => 'Email', 'value' => $tenant->user?->email],
                            ['label' => 'Phone', 'value' => $tenant->user?->phone],
                            ['label' => 'Portfolio', 'value' => $this->localized($tenant->portfolio?->name_en, $tenant->portfolio?->name_ar), 'href' => $tenant->portfolio ? route('portfolios.show', $tenant->portfolio) : null],
                            ['label' => 'National ID', 'value' => $tenant->national_id],
                            ['label' => 'Company', 'value' => $tenant->company_name],
                            ['label' => 'Emergency contact', 'value' => trim(($tenant->emergency_contact_name ?? '').' '.$tenant->emergency_contact_phone)],
                            ['label' => 'Address', 'value' => $tenant->address],
                            ['label' => 'Notes', 'value' => $tenant->notes],
                        ]),
                    ],
                    [
                        'title' => 'Current rental',
                        'description' => 'Active lease and remaining balance for this tenant.',
                        'items' => $this->detailItems([
                            ['label' => 'Lease', 'value' => $activeLease?->code, 'href' => $activeLease ? route('leases.show', $activeLease) : null],
                            ['label' => 'Asset', 'value' => $this->localized($activeLease?->leaseable?->title_en, $activeLease?->leaseable?->title_ar), 'href' => $activeLease?->leaseable ? route('assets.show', $activeLease->leaseable) : null],
                            ['label' => 'Contract ends', 'value' => $activeLease?->ends_at?->toDateString()],
                            ['label' => 'Balance', 'value' => $activeLease ? number_format((float) $activeLease->balance_remaining, 2).' '.$activeLease->currency : null],
                        ]),
                    ],
                ],
                'related' => [
                    [
                        'title' => 'Leases',
                        'description' => 'Current and historical rental contracts.',
                        'columns' => ['Lease', 'Asset', 'Status', 'Balance'],
                        'rows' => $tenant->leases->map(fn ($lease) => [
                            'Lease' => $lease->code,
                            'Asset' => $this->localized($lease->leaseable?->title_en, $lease->leaseable?->title_ar) ?? '-',
                            'Status' => $lease->status,
                            'Balance' => number_format((float) $lease->balance_remaining, 2).' '.$lease->currency,
                        ])->all(),
                        'emptyText' => 'No leases yet.',
                        'actionHref' => route('leases.create', ['tenant_profile_id' => $tenant->id]),
                        'actionLabel' => 'Create lease',
                    ],
                    [
                        'title' => 'Maintenance',
                        'description' => 'Requests submitted by this tenant.',
                        'columns' => ['Request', 'Asset', 'Status', 'Priority'],
                        'rows' => $tenant->maintenanceRequests->take(8)->map(fn ($maintenanceRequest) => [
                            'Request' => '#'.$maintenanceRequest->id.' '.$maintenanceRequest->title,
                            'Asset' => $this->localized($maintenanceRequest->asset?->title_en, $maintenanceRequest->asset?->title_ar) ?? '-',
                            'Status' => $maintenanceRequest->status,
                            'Priority' => $maintenanceRequest->priority,
                        ])->all(),
                        'emptyText' => 'No maintenance requests yet.',
                    ],
                ],
                'documents' => $activeLease ? $this->documentStrip($activeLease->documents) : [],
                'timeline' => $this->activityTimeline($tenant),
            ],
        ]);
    }

    public function edit(Request $request, TenantProfile $tenant): Response
    {
        $actor = $this->actor($request);
        $this->requireRoles($actor, ['superadmin', 'owner', 'property_manager']);
        $this->ensurePortfolioAccess($actor, $tenant->portfolio_id);
        $tenant->loadMissing('user');

        return Inertia::render('admin/resource-form', [
            'formPage' => $this->tenantFormPage($actor, $tenant),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $actor = $this->actor($request);
        $this->requireRoles($actor, ['superadmin', 'owner', 'property_manager']);

        $data = $request->validate([
            'portfolio_id' => ['nullable', 'integer', 'exists:portfolios,id'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'preferred_locale' => ['required', 'in:en,ar'],
            'password' => ['required', 'string', 'min:8'],
            'profile_type' => ['required', 'string'],
            'national_id' => ['nullable', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'status' => ['required', 'string'],
        ]);

        $portfolioId = $data['portfolio_id'] ?? $actor->portfolio_id;
        $this->ensurePortfolioAccess($actor, $portfolioId);

        $tenant = DB::transaction(function () use ($data, $portfolioId) {
            $user = User::query()->create([
                'portfolio_id' => $portfolioId,
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'preferred_locale' => $data['preferred_locale'],
                'status' => $this->userStatusFromTenantStatus($data['status']),
                'force_password_reset' => true,
                'password' => Hash::make($data['password']),
            ]);

            $user->syncRoles(['tenant']);

            return TenantProfile::query()->create([
                'portfolio_id' => $portfolioId,
                'user_id' => $user->id,
                'profile_type' => $data['profile_type'],
                'national_id' => $data['national_id'] ?? null,
                'company_name' => $data['company_name'] ?? null,
                'emergency_contact_name' => $data['emergency_contact_name'] ?? null,
                'emergency_contact_phone' => $data['emergency_contact_phone'] ?? null,
                'address' => $data['address'] ?? null,
                'status' => $data['status'],
                'notes' => $data['notes'] ?? null,
            ]);
        });

        return to_route('tenants.show', $tenant)->with('success', trans('app.messages.tenant_created'));
    }

    public function update(Request $request, TenantProfile $tenant): RedirectResponse
    {
        $actor = $this->actor($request);
        $this->requireRoles($actor, ['superadmin', 'owner', 'property_manager']);
        $this->ensurePortfolioAccess($actor, $tenant->portfolio_id);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'preferred_locale' => ['required', 'in:en,ar'],
            'profile_type' => ['required', 'string'],
            'national_id' => ['nullable', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'status' => ['required', 'string'],
        ]);

        $tenant->user?->update([
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'preferred_locale' => $data['preferred_locale'],
            'status' => $this->userStatusFromTenantStatus($data['status']),
        ]);

        $tenant->update([
            'profile_type' => $data['profile_type'],
            'national_id' => $data['national_id'] ?? null,
            'company_name' => $data['company_name'] ?? null,
            'emergency_contact_name' => $data['emergency_contact_name'] ?? null,
            'emergency_contact_phone' => $data['emergency_contact_phone'] ?? null,
            'address' => $data['address'] ?? null,
            'status' => $data['status'],
            'notes' => $data['notes'] ?? null,
        ]);

        return to_route('tenants.show', $tenant)->with('success', trans('app.messages.tenant_updated'));
    }

    public function destroy(Request $request, TenantProfile $tenant): RedirectResponse
    {
        $actor = $this->actor($request);
        $this->requireRoles($actor, ['superadmin', 'owner', 'property_manager']);
        $this->ensurePortfolioAccess($actor, $tenant->portfolio_id);

        if ($tenant->leases()->where('status', 'active')->exists()) {
            return back()->with('error', trans('app.errors.tenant_has_active_lease'));
        }

        DB::transaction(function () use ($tenant) {
            $tenant->update(['status' => 'blocked']);
            $tenant->user?->update(['status' => 'suspended']);
        });

        return to_route('tenants.index')->with('success', trans('app.messages.tenant_archived'));
    }

    /**
     * @return array<string, int>
     */
    private function tenantInsights(Builder $baseQuery): array
    {
        return [
            'total' => (clone $baseQuery)->count(),
            'active' => (clone $baseQuery)->where('status', 'active')->count(),
            'blocked' => (clone $baseQuery)->where('status', 'blocked')->count(),
            'companies' => (clone $baseQuery)->where('profile_type', 'company')->count(),
            'without_active_lease' => (clone $baseQuery)
                ->whereDoesntHave('leases', fn (Builder $query) => $query->where('status', 'active'))
                ->count(),
            'missing_emergency' => (clone $baseQuery)
                ->where(function (Builder $query): void {
                    $query
                        ->whereNull('emergency_contact_name')
                        ->orWhereNull('emergency_contact_phone')
                        ->orWhere('emergency_contact_name', '')
                        ->orWhere('emergency_contact_phone', '');
                })
                ->count(),
            'missing_address' => (clone $baseQuery)
                ->where(function (Builder $query): void {
                    $query->whereNull('address')->orWhere('address', '');
                })
                ->count(),
        ];
    }

    private function userStatusFromTenantStatus(string $tenantStatus): string
    {
        return match ($tenantStatus) {
            'blocked' => 'suspended',
            'inactive' => 'inactive',
            default => 'active',
        };
    }

    private function tenantFormPage(User $actor, ?TenantProfile $tenant = null): array
    {
        $fields = [];

        if ($actor->hasRole('superadmin') && $tenant === null) {
            $fields[] = [
                'name' => 'portfolio_id',
                'label' => 'Portfolio',
                'type' => 'select',
                'options' => collect($this->portfolioOptions($actor))->map(fn ($portfolio) => ['value' => $portfolio['id'], 'label' => $portfolio['name']])->all(),
            ];
        }

        $fields = [
            ...$fields,
            ['name' => 'name', 'label' => 'Tenant name', 'required' => true],
        ];

        if ($tenant === null) {
            $fields[] = ['name' => 'email', 'label' => 'Login email', 'type' => 'email', 'required' => true];
            $fields[] = ['name' => 'password', 'label' => 'Temporary password', 'type' => 'password', 'required' => true];
        }

        $fields = [
            ...$fields,
            ['name' => 'phone', 'label' => 'Phone'],
            ['name' => 'preferred_locale', 'label' => 'Portal language', 'type' => 'select', 'options' => [['value' => 'en', 'label' => 'English'], ['value' => 'ar', 'label' => 'Arabic']]],
            ['name' => 'profile_type', 'label' => 'Profile type', 'type' => 'select', 'options' => $this->fieldOptions(['individual', 'company'])],
            ['name' => 'national_id', 'label' => 'National ID / registration'],
            ['name' => 'company_name', 'label' => 'Company name'],
            ['name' => 'emergency_contact_name', 'label' => 'Emergency contact name'],
            ['name' => 'emergency_contact_phone', 'label' => 'Emergency contact phone'],
            ['name' => 'address', 'label' => 'Address', 'type' => 'textarea', 'rows' => 2],
            ['name' => 'notes', 'label' => 'Notes', 'type' => 'textarea'],
            ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => $this->fieldOptions(['active', 'inactive', 'blocked'])],
        ];

        return [
            'title' => $tenant ? 'Edit tenant' : 'Create tenant',
            'description' => 'Create the tenant profile and portal login before connecting a lease.',
            'backHref' => $tenant ? route('tenants.show', $tenant) : route('tenants.index'),
            'backLabel' => $tenant ? 'Tenant detail' : 'All tenants',
            'action' => $tenant ? route('tenants.update', $tenant) : route('tenants.store'),
            'method' => $tenant ? 'put' : 'post',
            'submitLabel' => $tenant ? 'Update tenant' : 'Create tenant',
            'fields' => $fields,
            'initialValues' => [
                'portfolio_id' => (string) ($tenant?->portfolio_id ?? request('portfolio_id', $actor->portfolio_id ?? '')),
                'name' => $tenant?->user?->name ?? '',
                'email' => $tenant?->user?->email ?? '',
                'password' => '',
                'phone' => $tenant?->user?->phone ?? '',
                'preferred_locale' => $tenant?->user?->preferred_locale ?? 'en',
                'profile_type' => $tenant?->profile_type ?? 'individual',
                'national_id' => $tenant?->national_id ?? '',
                'company_name' => $tenant?->company_name ?? '',
                'emergency_contact_name' => $tenant?->emergency_contact_name ?? '',
                'emergency_contact_phone' => $tenant?->emergency_contact_phone ?? '',
                'address' => $tenant?->address ?? '',
                'notes' => $tenant?->notes ?? '',
                'status' => $tenant?->status ?? 'active',
            ],
        ];
    }
}
