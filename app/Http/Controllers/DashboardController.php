<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\CmsPage;
use App\Models\ExpenseEntry;
use App\Models\Lease;
use App\Models\MaintenanceRequest;
use App\Models\Payment;
use App\Models\Portfolio;
use App\Models\TenantProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $this->actor($request);

        if ($user->hasRole('tenant')) {
            $tenantProfile = TenantProfile::query()
                ->where('user_id', $user->id)
                ->with([
                    'leases' => fn ($query) => $query
                        ->whereIn('status', ['active', 'draft'])
                        ->with(['installments', 'documents', 'leaseable']),
                    'maintenanceRequests',
                    'payments',
                ])
                ->first();

            $activeLease = $tenantProfile?->leases->firstWhere('status', 'active') ?? $tenantProfile?->leases->first();

            return Inertia::render('dashboard', [
                'mode' => 'tenant',
                'stats' => [
                    'leaseCode' => $activeLease?->code,
                    'daysLeft' => $activeLease?->days_remaining,
                    'amountLeft' => $activeLease?->balance_remaining ?? 0,
                    'paidAmount' => $activeLease?->total_paid ?? 0,
                    'maintenanceRequests' => $tenantProfile?->maintenanceRequests->count() ?? 0,
                ],
                'tenantPortal' => [
                    'tenant' => $tenantProfile,
                    'lease' => $activeLease,
                    'payments' => $tenantProfile?->payments()->latest('received_on')->limit(8)->get() ?? [],
                    'requests' => $tenantProfile?->maintenanceRequests()->latest()->limit(8)->get() ?? [],
                    'documents' => $activeLease?->documents()->latest()->get() ?? [],
                ],
            ]);
        }

        $assetQuery = $this->scopeByPortfolio(Asset::query(), $user);
        $leaseQuery = $this->scopeByPortfolio(Lease::query(), $user);
        $paymentQuery = $this->scopeByPortfolio(Payment::query(), $user);
        $maintenanceQuery = $this->scopeByPortfolio(MaintenanceRequest::query(), $user);
        $expenseQuery = $this->scopeByPortfolio(ExpenseEntry::query(), $user);

        $stats = [
            'totalUsers' => $user->hasRole('superadmin')
                ? User::query()->count()
                : User::query()->where('portfolio_id', $user->portfolio_id)->count(),
            'totalPortfolios' => $user->hasRole('superadmin') ? Portfolio::query()->count() : 1,
            'totalAssets' => (clone $assetQuery)->count(),
            'totalValue' => (float) (clone $assetQuery)->sum('valuation_amount'),
            'activeLeases' => (clone $leaseQuery)->where('status', 'active')->count(),
            'monthlyRevenue' => (float) (clone $paymentQuery)
                ->whereMonth('received_on', now()->month)
                ->whereYear('received_on', now()->year)
                ->sum('amount'),
            'monthlyExpenses' => (float) (clone $expenseQuery)
                ->whereMonth('incurred_on', now()->month)
                ->whereYear('incurred_on', now()->year)
                ->sum('amount'),
            'openRequests' => (clone $maintenanceQuery)->whereIn('status', ['open', 'in_progress'])->count(),
            'arrears' => (float) (clone $leaseQuery)
                ->with('installments')
                ->get()
                ->sum(fn (Lease $lease) => $lease->balance_remaining),
            'vacantUnits' => (clone $assetQuery)->where('rentable', true)->where('occupancy_status', 'vacant')->count(),
        ];

        $occupancy = (clone $assetQuery)
            ->selectRaw('occupancy_status, COUNT(*) as total')
            ->groupBy('occupancy_status')
            ->pluck('total', 'occupancy_status');

        $paymentHealth = (clone $leaseQuery)
            ->with(['tenantProfile.user', 'installments'])
            ->get()
            ->map(fn (Lease $lease) => [
                'code' => $lease->code,
                'tenant' => $lease->tenantProfile?->user?->name,
                'due' => $lease->total_due,
                'paid' => $lease->total_paid,
                'remaining' => $lease->balance_remaining,
            ]);

        $assetMix = (clone $assetQuery)
            ->selectRaw('asset_type, COUNT(*) as total')
            ->groupBy('asset_type')
            ->pluck('total', 'asset_type');

        $maintenanceByStatus = (clone $maintenanceQuery)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $expiringLeases = (clone $leaseQuery)
            ->with(['tenantProfile.user', 'leaseable', 'installments'])
            ->where('status', 'active')
            ->whereDate('ends_at', '<=', now()->addDays(90))
            ->orderBy('ends_at')
            ->limit(8)
            ->get()
            ->map(fn (Lease $lease) => [
                'id' => $lease->id,
                'code' => $lease->code,
                'tenant' => $lease->tenantProfile?->user?->name,
                'asset' => $lease->leaseable?->title_en,
                'ends_at' => $lease->ends_at?->toDateString(),
                'days_remaining' => $lease->days_remaining,
                'balance_remaining' => $lease->balance_remaining,
                'currency' => $lease->currency,
            ]);

        $arrearsLeases = (clone $leaseQuery)
            ->with(['tenantProfile.user', 'leaseable', 'installments'])
            ->whereIn('status', ['active', 'expired'])
            ->get()
            ->filter(fn (Lease $lease) => $lease->balance_remaining > 0)
            ->sortByDesc(fn (Lease $lease) => $lease->balance_remaining)
            ->take(8)
            ->values()
            ->map(fn (Lease $lease) => [
                'id' => $lease->id,
                'code' => $lease->code,
                'tenant' => $lease->tenantProfile?->user?->name,
                'asset' => $lease->leaseable?->title_en,
                'balance_remaining' => $lease->balance_remaining,
                'currency' => $lease->currency,
            ]);

        $setupChecklist = [
            ['label' => 'Create portfolio', 'done' => $user->hasRole('superadmin') ? Portfolio::query()->exists() : (bool) $user->portfolio_id, 'href' => '/portfolios'],
            ['label' => 'Create users', 'done' => $stats['totalUsers'] > 1, 'href' => '/users'],
            ['label' => 'Add assets', 'done' => $stats['totalAssets'] > 0, 'href' => '/assets'],
            ['label' => 'Add tenants', 'done' => (clone $this->scopeByPortfolio(TenantProfile::query(), $user))->exists(), 'href' => '/tenants'],
            ['label' => 'Create leases', 'done' => $stats['activeLeases'] > 0, 'href' => '/leases'],
            ['label' => 'Publish website', 'done' => CmsPage::query()->where('status', 'published')->exists(), 'href' => '/cms'],
        ];

        return Inertia::render('dashboard', [
            'mode' => $user->hasRole('superadmin') ? 'superadmin' : 'portfolio',
            'stats' => $stats,
            'charts' => [
                'occupancy' => $occupancy,
                'paymentHealth' => $paymentHealth,
                'assetMix' => $assetMix,
                'maintenanceByStatus' => $maintenanceByStatus,
            ],
            'setupChecklist' => $setupChecklist,
            'expiringLeases' => $expiringLeases,
            'arrearsLeases' => $arrearsLeases,
            'cmsStatus' => [
                'published' => CmsPage::query()->where('status', 'published')->count(),
                'draft' => CmsPage::query()->where('status', 'draft')->count(),
                'homepage' => CmsPage::query()->where('is_homepage', true)->value('title_en'),
            ],
            'recentPayments' => (clone $paymentQuery)->with('tenantProfile.user')->latest('received_on')->limit(8)->get(),
            'recentMaintenance' => (clone $maintenanceQuery)->with('asset')->latest()->limit(8)->get(),
        ]);
    }
}
