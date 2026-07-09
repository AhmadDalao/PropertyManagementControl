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

        return to_route('portfolios.index')->with('success', "Portfolio {$portfolio->name_en} created.");
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
            'default_currency' => ['nullable', 'string', 'size:3'],
            'status' => ['required', Rule::in($this->statuses)],
            'module_settings' => ['nullable', 'array'],
            'module_settings.*' => ['boolean'],
        ]);

        $data['module_settings'] = PortfolioModules::normalize($data['module_settings'] ?? $portfolio->module_settings);

        $portfolio->update($data);

        return to_route('portfolios.index')->with('success', "Portfolio {$portfolio->name_en} updated.");
    }

    public function destroy(Request $request, Portfolio $portfolio): RedirectResponse
    {
        $user = $this->actor($request);
        $this->requireRoles($user, ['superadmin']);

        $portfolio->update(['status' => 'archived']);

        return to_route('portfolios.index')->with('success', "Portfolio {$portfolio->name_en} archived.");
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
}
