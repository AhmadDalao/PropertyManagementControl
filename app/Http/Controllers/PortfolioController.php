<?php

namespace App\Http\Controllers;

use App\Models\Portfolio;
use App\Support\PortfolioModules;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PortfolioController extends Controller
{
    /**
     * @var array<int, string>
     */
    private array $statuses = ['active', 'inactive', 'archived'];

    public function index(Request $request): Response
    {
        $user = $this->actor($request);
        $this->requireRoles($user, ['superadmin', 'owner', 'property_manager']);

        $filters = $this->tableFilters($request, ['status' => 'all']);
        $baseQuery = $this->scopeByPortfolio(Portfolio::query(), $user, 'id');
        $portfolios = (clone $baseQuery)
            ->with(['owner', 'users'])
            ->withCount([
                'assets',
                'users',
                'leases',
                'leases as active_leases_count' => fn ($query) => $query->where('status', 'active'),
                'maintenanceRequests as open_maintenance_count' => fn ($query) => $query->whereIn('status', ['open', 'in_progress']),
            ])
            ->withSum(['assets as valuation_total' => fn ($query) => $query->where('status', '!=', 'archived')], 'valuation_amount')
            ->withSum(['payments as posted_revenue_total' => fn ($query) => $query->where('status', 'posted')], 'amount');

        $this->applySearch($portfolios, $filters['search'], [
            'name_en',
            'name_ar',
            'code',
            'contact_email',
            'contact_phone',
            'city',
            'country',
            'address',
            'address_ar',
        ]);
        $this->applyExactFilter($portfolios, $filters, 'status');

        return Inertia::render('admin/portfolios/index', [
            'portfolios' => $this->paginateTable($portfolios, $request, $filters, [
                'created_at',
                'name_en',
                'code',
                'status',
                'city',
            ]),
            'portfolioInsights' => $this->portfolioInsights($baseQuery),
            'filters' => $filters,
            'counts' => $this->statusCounts($baseQuery, $this->statuses, $filters),
            'canCreate' => $user->hasRole('superadmin'),
            'canUpdate' => $user->hasAnyRole(['superadmin', 'owner']),
            'moduleDefinitions' => PortfolioModules::definitions(),
            'statusOptions' => $this->statuses,
        ]);
    }

    public function create(Request $request): Response
    {
        $user = $this->actor($request);
        $this->requireRoles($user, ['superadmin']);

        return Inertia::render('admin/resource-form', [
            'formPage' => $this->portfolioFormPage(),
        ]);
    }

    public function show(Request $request, Portfolio $portfolio): Response
    {
        $user = $this->actor($request);
        $this->requireRoles($user, ['superadmin', 'owner', 'property_manager']);
        $this->ensurePortfolioAccess($user, $portfolio->id);

        $portfolio->loadMissing([
            'owner',
            'users.roles',
            'assets.stakeholders.user',
            'leases.tenantProfile.user',
            'payments',
            'maintenanceRequests.asset',
            'expenseEntries',
        ]);
        $localizedPortfolioName = $this->localized($portfolio->name_en, $portfolio->name_ar);
        $localizedAddress = $this->localized($portfolio->address, $portfolio->address_ar);
        $moduleLabels = collect(PortfolioModules::definitions())->keyBy('key');

        return Inertia::render('admin/resource-show', [
            'detailPage' => [
                'header' => [
                    'eyebrow' => 'Portfolio detail',
                    'title' => $localizedPortfolioName,
                    'description' => trim($portfolio->code.' · '.$portfolio->city.' · '.$portfolio->country),
                    'backHref' => route('portfolios.index'),
                    'backLabel' => 'All portfolios',
                    'actions' => [
                        ['label' => 'Edit portfolio', 'href' => route('portfolios.edit', $portfolio), 'variant' => 'primary'],
                        ['label' => 'Create asset', 'href' => route('assets.create', ['portfolio_id' => $portfolio->id]), 'variant' => 'secondary'],
                        ['label' => 'Create user', 'href' => route('users.create', ['portfolio_id' => $portfolio->id]), 'variant' => 'secondary'],
                    ],
                ],
                'stats' => $this->detailItems([
                    ['label' => 'Assets', 'value' => $portfolio->assets->count(), 'tone' => 'primary'],
                    ['label' => 'Users', 'value' => $portfolio->users->count()],
                    ['label' => 'Active leases', 'value' => $portfolio->leases->where('status', 'active')->count(), 'tone' => 'teal'],
                    ['label' => 'Open maintenance', 'value' => $portfolio->maintenanceRequests->whereIn('status', ['open', 'in_progress'])->count(), 'tone' => 'danger'],
                ]),
                'sections' => [
                    [
                        'title' => 'Business profile',
                        'description' => 'Client account boundary and default settings.',
                        'items' => $this->detailItems([
                            ['label' => 'Arabic name', 'value' => $portfolio->name_ar],
                            ['label' => 'Code', 'value' => $portfolio->code],
                            ['label' => 'Status', 'value' => $portfolio->status],
                            ['label' => 'Default currency', 'value' => $portfolio->default_currency],
                            ['label' => 'Contact email', 'value' => $portfolio->contact_email],
                            ['label' => 'Contact phone', 'value' => $portfolio->contact_phone],
                            ['label' => 'Address', 'value' => $localizedAddress],
                        ]),
                    ],
                    [
                        'title' => 'Ownership',
                        'description' => 'Portfolio owner and module posture.',
                        'items' => $this->detailItems([
                            ['label' => 'Owner', 'value' => $portfolio->owner?->name, 'href' => $portfolio->owner ? route('users.show', $portfolio->owner) : null],
                            [
                                'label' => 'Enabled modules',
                                'value' => collect($portfolio->module_settings ?? [])
                                    ->filter()
                                    ->keys()
                                    ->map(fn (string $key) => $moduleLabels->get($key)['label'] ?? $key)
                                    ->implode(', '),
                            ],
                        ]),
                    ],
                ],
                'related' => [
                    [
                        'title' => 'Recent assets',
                        'description' => 'Property nodes in this portfolio.',
                        'columns' => ['Asset', 'Code', 'Type', 'Occupancy'],
                        'rows' => $portfolio->assets->take(8)->map(fn ($asset) => [
                            'Asset' => $this->localized($asset->title_en, $asset->title_ar),
                            'Code' => $asset->code,
                            'Type' => $asset->asset_type,
                            'Occupancy' => $asset->occupancy_status,
                        ])->all(),
                        'emptyText' => 'No assets yet.',
                        'actionHref' => route('assets.create', ['portfolio_id' => $portfolio->id]),
                        'actionLabel' => 'Add asset',
                    ],
                    [
                        'title' => 'People',
                        'description' => 'Owners, managers, and tenants under this account.',
                        'columns' => ['User', 'Email', 'Role', 'Status'],
                        'rows' => $portfolio->users->take(8)->map(fn ($portfolioUser) => [
                            'User' => $portfolioUser->name,
                            'Email' => $portfolioUser->email,
                            'Role' => $portfolioUser->roles
                                ->pluck('name')
                                ->map(fn (string $role) => trans("app.roles.{$role}"))
                                ->implode(', '),
                            'Status' => $portfolioUser->status,
                        ])->all(),
                        'emptyText' => 'No users yet.',
                        'actionHref' => route('users.create', ['portfolio_id' => $portfolio->id]),
                        'actionLabel' => 'Add user',
                    ],
                ],
                'documents' => [],
                'timeline' => $this->activityTimeline($portfolio),
            ],
        ]);
    }

    public function edit(Request $request, Portfolio $portfolio): Response
    {
        $user = $this->actor($request);
        $this->requireRoles($user, ['superadmin', 'owner']);
        $this->ensurePortfolioAccess($user, $portfolio->id);

        return Inertia::render('admin/resource-form', [
            'formPage' => $this->portfolioFormPage($portfolio),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $this->actor($request);
        $this->requireRoles($user, ['superadmin']);

        $data = $request->validate([
            'name_en' => ['required', 'string', 'max:255'],
            'name_ar' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50', 'unique:portfolios,code'],
            'contact_email' => ['nullable', 'email'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'city' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'address_ar' => ['nullable', 'string'],
            'default_currency' => ['nullable', 'string', 'size:3'],
            'status' => ['required', Rule::in($this->statuses)],
            'module_settings' => ['nullable', 'array'],
            'module_settings.*' => ['boolean'],
        ]);

        $portfolio = Portfolio::query()->create([
            ...$data,
            'code' => $data['code'] ?: Str::upper(Str::random(6)),
            'slug' => Str::slug($data['name_en']).'-'.Str::lower(Str::random(4)),
            'module_settings' => PortfolioModules::normalize($data['module_settings'] ?? null),
        ]);

        return to_route('portfolios.show', $portfolio)->with('success', trans('app.messages.record_created', [
            'resource' => trans('app.nav.portfolios'),
            'name' => app()->isLocale('ar') ? $portfolio->name_ar : $portfolio->name_en,
        ]));
    }

    public function update(Request $request, Portfolio $portfolio): RedirectResponse
    {
        $user = $this->actor($request);
        $this->requireRoles($user, ['superadmin', 'owner']);
        $this->ensurePortfolioAccess($user, $portfolio->id);

        $data = $request->validate([
            'name_en' => ['required', 'string', 'max:255'],
            'name_ar' => ['required', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'city' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'address_ar' => ['nullable', 'string'],
            'default_currency' => ['nullable', 'string', 'size:3'],
            'status' => ['required', Rule::in($this->statuses)],
            'module_settings' => ['nullable', 'array'],
            'module_settings.*' => ['boolean'],
        ]);

        $data['module_settings'] = PortfolioModules::normalize($data['module_settings'] ?? $portfolio->module_settings);

        $portfolio->update($data);

        return to_route('portfolios.show', $portfolio)->with('success', trans('app.messages.record_updated', [
            'resource' => trans('app.nav.portfolios'),
            'name' => app()->isLocale('ar') ? $portfolio->name_ar : $portfolio->name_en,
        ]));
    }

    public function destroy(Request $request, Portfolio $portfolio): RedirectResponse
    {
        $user = $this->actor($request);
        $this->requireRoles($user, ['superadmin']);

        $portfolio->update(['status' => 'archived']);

        return to_route('portfolios.index')->with('success', trans('app.messages.portfolio_archived', [
            'name' => app()->isLocale('ar') ? $portfolio->name_ar : $portfolio->name_en,
        ]));
    }

    /**
     * @return array<string, int|float>
     */
    private function portfolioInsights(Builder $baseQuery): array
    {
        $portfolios = (clone $baseQuery)
            ->withCount([
                'assets',
                'users',
                'leases',
                'leases as active_leases_count' => fn ($query) => $query->where('status', 'active'),
                'maintenanceRequests as open_maintenance_count' => fn ($query) => $query->whereIn('status', ['open', 'in_progress']),
            ])
            ->withSum(['assets as valuation_total' => fn ($query) => $query->where('status', '!=', 'archived')], 'valuation_amount')
            ->withSum(['payments as posted_revenue_total' => fn ($query) => $query->where('status', 'posted')], 'amount')
            ->get();

        return [
            'total' => $portfolios->count(),
            'active' => $portfolios->where('status', 'active')->count(),
            'inactive' => $portfolios->where('status', 'inactive')->count(),
            'archived' => $portfolios->where('status', 'archived')->count(),
            'assets' => (int) $portfolios->sum('assets_count'),
            'users' => (int) $portfolios->sum('users_count'),
            'leases' => (int) $portfolios->sum('leases_count'),
            'active_leases' => (int) $portfolios->sum('active_leases_count'),
            'open_maintenance' => (int) $portfolios->sum('open_maintenance_count'),
            'valuation_total' => (float) $portfolios->sum('valuation_total'),
            'posted_revenue_total' => (float) $portfolios->sum('posted_revenue_total'),
        ];
    }

    private function portfolioFormPage(?Portfolio $portfolio = null): array
    {
        $fields = [
            ['name' => 'name_en', 'label' => 'English name', 'required' => true],
            ['name' => 'name_ar', 'label' => 'Arabic name', 'required' => true],
        ];

        if ($portfolio === null) {
            $fields[] = ['name' => 'code', 'label' => 'Code', 'help' => 'Leave blank to auto-generate.'];
        }

        $fields = [
            ...$fields,
            ['name' => 'contact_email', 'label' => 'Contact email', 'type' => 'email'],
            ['name' => 'contact_phone', 'label' => 'Contact phone'],
            ['name' => 'city', 'label' => 'City'],
            ['name' => 'country', 'label' => 'Country'],
            ['name' => 'address', 'label' => trans('app.fields.address_en'), 'type' => 'textarea', 'rows' => 2],
            ['name' => 'address_ar', 'label' => trans('app.fields.address_ar'), 'type' => 'textarea', 'rows' => 2],
            ['name' => 'default_currency', 'label' => 'Default currency', 'placeholder' => 'SAR'],
            ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => $this->fieldOptions($this->statuses), 'required' => true],
        ];

        return [
            'title' => $portfolio
                ? trans('app.actions.edit').' '.$this->localized($portfolio->name_en, $portfolio->name_ar)
                : 'Create portfolio',
            'description' => 'Portfolios are the client boundary. Users, assets, leases, payments, and reports hang from here.',
            'backHref' => $portfolio ? route('portfolios.show', $portfolio) : route('portfolios.index'),
            'backLabel' => $portfolio ? 'Portfolio detail' : 'All portfolios',
            'action' => $portfolio ? route('portfolios.update', $portfolio) : route('portfolios.store'),
            'method' => $portfolio ? 'put' : 'post',
            'submitLabel' => $portfolio ? 'Update portfolio' : 'Create portfolio',
            'fields' => $fields,
            'initialValues' => [
                'name_en' => $portfolio?->name_en ?? '',
                'name_ar' => $portfolio?->name_ar ?? '',
                'code' => $portfolio?->code ?? '',
                'contact_email' => $portfolio?->contact_email ?? '',
                'contact_phone' => $portfolio?->contact_phone ?? '',
                'city' => $portfolio?->city ?? '',
                'country' => $portfolio?->country ?? 'Saudi Arabia',
                'address' => $portfolio?->address ?? '',
                'address_ar' => $portfolio?->address_ar ?? '',
                'default_currency' => $portfolio?->default_currency ?? 'SAR',
                'status' => $portfolio?->status ?? 'active',
            ],
        ];
    }
}
