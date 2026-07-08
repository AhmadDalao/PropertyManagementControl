<?php

namespace App\Http\Controllers;

use App\Models\Asset;
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
                        ->with('installments', 'documents'),
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

        return Inertia::render('dashboard', [
            'mode' => $user->hasRole('superadmin') ? 'superadmin' : 'portfolio',
            'stats' => $stats,
            'charts' => [
                'occupancy' => $occupancy,
                'paymentHealth' => $paymentHealth,
            ],
            'recentPayments' => (clone $paymentQuery)->with('tenantProfile.user')->latest('received_on')->limit(8)->get(),
            'recentMaintenance' => (clone $maintenanceQuery)->with('asset')->latest()->limit(8)->get(),
        ]);
    }
}
