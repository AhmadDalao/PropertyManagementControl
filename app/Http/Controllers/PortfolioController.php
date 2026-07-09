<?php

namespace App\Http\Controllers;

use App\Models\Portfolio;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PortfolioController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $this->actor($request);
        $this->requireRoles($user, ['superadmin', 'owner', 'property_manager']);

        $filters = $this->tableFilters($request, ['status' => 'all']);
        $baseQuery = $this->scopeByPortfolio(Portfolio::query(), $user, 'id');
        $portfolios = (clone $baseQuery)
            ->with(['owner', 'users'])
            ->withCount(['assets', 'users', 'leases']);

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
            'filters' => $filters,
            'counts' => $this->statusCounts($baseQuery, ['active', 'inactive', 'archived'], $filters),
            'canCreate' => $user->hasRole('superadmin'),
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
            'status' => ['required', 'string', 'max:50'],
        ]);

        $portfolio = Portfolio::query()->create([
            ...$data,
            'code' => $data['code'] ?: Str::upper(Str::random(6)),
            'slug' => Str::slug($data['name_en']).'-'.Str::lower(Str::random(4)),
            'module_settings' => [
                'assets' => true,
                'tenants' => true,
                'leases' => true,
                'payments' => true,
                'maintenance' => true,
            ],
        ]);

        return to_route('portfolios.index')->with('success', "Portfolio {$portfolio->name_en} created.");
    }

    public function update(Request $request, Portfolio $portfolio): RedirectResponse
    {
        $user = $this->actor($request);
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
            'status' => ['required', 'string', 'max:50'],
            'module_settings' => ['nullable', 'array'],
        ]);

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
}
