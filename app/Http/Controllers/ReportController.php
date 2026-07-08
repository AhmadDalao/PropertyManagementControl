<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\ExpenseEntry;
use App\Models\Lease;
use App\Models\Payment;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        $actor = $this->actor($request);

        if ($actor->hasRole('tenant')) {
            return Inertia::render('admin/reports/index', [
                'mode' => 'tenant',
                'summary' => [],
                'charts' => [],
            ]);
        }

        $this->requireRoles($actor, ['superadmin', 'owner', 'property_manager']);

        $leases = $this->scopeByPortfolio(Lease::query()->with('installments'), $actor)->get();
        $payments = $this->scopeByPortfolio(Payment::query(), $actor)->get();
        $expenses = $this->scopeByPortfolio(ExpenseEntry::query(), $actor)->get();
        $assets = $this->scopeByPortfolio(Asset::query(), $actor)->get();

        return Inertia::render('admin/reports/index', [
            'mode' => $actor->hasRole('superadmin') ? 'superadmin' : 'portfolio',
            'summary' => [
                'revenue' => (float) $payments->sum('amount'),
                'expenses' => (float) $expenses->sum('amount'),
                'net' => (float) $payments->sum('amount') - (float) $expenses->sum('amount'),
                'occupancyRate' => $assets->count() > 0
                    ? round(($assets->where('occupancy_status', 'occupied')->count() / $assets->count()) * 100, 2)
                    : 0,
                'arrears' => (float) $leases->sum(fn (Lease $lease) => $lease->balance_remaining),
            ],
            'charts' => [
                'revenueByMonth' => $payments
                    ->groupBy(fn ($payment) => $payment->received_on?->format('Y-m'))
                    ->map(fn ($group) => (float) $group->sum('amount')),
                'expenseByCategory' => $expenses
                    ->groupBy('category')
                    ->map(fn ($group) => (float) $group->sum('amount')),
                'assetMix' => $assets
                    ->groupBy('asset_type')
                    ->map(fn ($group) => $group->count()),
            ],
            'leases' => $leases,
        ]);
    }
}
